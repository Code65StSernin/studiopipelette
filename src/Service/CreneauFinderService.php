<?php

namespace App\Service;

use App\Entity\UnavailabilityRule;
use App\Repository\TarifRepository;
use App\Repository\ContraintePrestationRepository;
use App\Repository\DispoPrestationRepository;
use App\Repository\ReservationRepository;
use App\Repository\UnavailabilityRuleRepository;
use DateTime;
use DateTimeZone;

class CreneauFinderService
{
    private UnavailabilityRuleRepository $unavailabilityRuleRepository;
    private TarifRepository $tarifRepository;
    private ReservationRepository $reservationRepository;
    private DispoPrestationRepository $dispoPrestationRepository;
    private ContraintePrestationRepository $contraintePrestationRepository;

    private const OPENING_HOUR = 8;
    private const CLOSING_HOUR = 20;

    public function __construct(
        UnavailabilityRuleRepository $unavailabilityRuleRepository,
        TarifRepository $tarifRepository,
        ReservationRepository $reservationRepository,
        DispoPrestationRepository $dispoPrestationRepository,
        ContraintePrestationRepository $contraintePrestationRepository
    ) {
        $this->unavailabilityRuleRepository = $unavailabilityRuleRepository;
        $this->tarifRepository = $tarifRepository;
        $this->reservationRepository = $reservationRepository;
        $this->dispoPrestationRepository = $dispoPrestationRepository;
        $this->contraintePrestationRepository = $contraintePrestationRepository;
    }

    /**
     * Retourne les créneaux disponibles pour les 3 prochains jours.
     */
    public function trouverProchainsCreneauxDisponibles(array $tarifIds = []): array
    {
        // 1. Calcul de la durée
        $duree = 15; // Valeur par défaut si aucun tarif
        if (!empty($tarifIds)) {
            $tarifs = $this->tarifRepository->findBy(['id' => $tarifIds]);
            $dureeTotal = array_sum(array_map(fn($t) => $t->getDureeMinutes(), $tarifs));
            if ($dureeTotal > 0) {
                $duree = $dureeTotal;
            }
        }

        // 2. Récupération des règles d'indisponibilité actives
        $rules = $this->unavailabilityRuleRepository->findBy(['active' => true]);

        // Récupération des contraintes de prestation actives
        $constraints = $this->contraintePrestationRepository->findActive();

        // 3. Recherche de 3 jours disponibles
        $result = [];
        $now = new DateTime("now", new DateTimeZone('Europe/Paris'));
        $today = (clone $now)->setTime(0, 0, 0);
        
        $daysFound = 0;
        $offset = 0;
        
        // On cherche jusqu'à trouver 3 jours ou qu'on ait parcouru 30 jours (sécurité)
        while ($daysFound < 3 && $offset < 30) {
            $currentDate = (clone $today)->modify("+$offset days");
            $dateKey = $currentDate->format('Y-m-d');
            
            // DispoPrestation: si une des prestations sélectionnées est suspendue ce jour,
            // on ne propose aucun créneau pour ce jour.
            $suspensions = !empty($tarifIds)
                ? $this->dispoPrestationRepository->findSuspensionsPourDate($currentDate, $tarifIds)
                : [];
            if (!empty($suspensions)) {
                $offset++;
                continue;
            }

            // ContraintePrestation
            if ($this->isDayRestrictedByConstraint($currentDate, $tarifIds, $constraints)) {
                $offset++;
                continue;
            }
            
            $slots = [];
            $reservations = $this->reservationRepository->findReservationsByDay($currentDate);
            $blocks = [];
            foreach ($reservations as $res) {
                $rStart = (new DateTime($res->getDateStart()->format('Y-m-d H:i:s'), new DateTimeZone('Europe/Paris')));
                $rEnd = (new DateTime($res->getDateEnd()->format('Y-m-d H:i:s'), new DateTimeZone('Europe/Paris')));
                $roundedEnd = $this->roundUpToNextQuarter($rEnd);
                $blocks[] = [$rStart, $roundedEnd];
            }
            // On scanne la journée de 08:00 à 20:00 par pas de 15 min
            $cursor = (clone $currentDate)->setTime(self::OPENING_HOUR, 0, 0);
            $endOfDay = (clone $currentDate)->setTime(self::CLOSING_HOUR, 0, 0);

            while ($cursor < $endOfDay) {
                // Si c'est aujourd'hui, on ne propose pas les créneaux passés
                if ($cursor < $now) {
                    $cursor->modify("+15 minutes");
                    continue;
                }

                $slotEnd = (clone $cursor)->modify("+$duree minutes");

                // Si le créneau dépasse la journée en cours ou l'heure de fermeture
                if ($slotEnd->format('Y-m-d') !== $dateKey || $slotEnd > $endOfDay) {
                    break;
                }

                // Vérification des règles d'indisponibilité
                if ($this->isSlotAvailable($cursor, $slotEnd, $rules) && !$this->overlapsAnyBlock($cursor, $slotEnd, $blocks)) {
                    $slots[] = [
                        'heure' => $cursor->format('H:i'),
                        'heureFin' => $slotEnd->format('H:i'),
                        'duree' => $duree
                    ];
                }

                $cursor->modify("+15 minutes"); 
            }

            // Si des créneaux sont trouvés pour ce jour, on l'ajoute
            if (!empty($slots)) {
                $result[$dateKey] = [
                    'date' => $currentDate,
                    'creneaux' => $slots
                ];
                $daysFound++;
            }
            
            $offset++;
        }

        return $result;
    }

    /**
     * Vérifie la disponibilité d'un créneau spécifique (Validation).
     */
    public function estCreneauDisponible($dateStr, $heureStr, $duree, $tarifIds): array
    {
        try {
            $start = new DateTime("$dateStr $heureStr", new DateTimeZone('Europe/Paris'));
            $end = (clone $start)->modify("+$duree minutes");
            
            // Vérification des horaires d'ouverture
            $opening = (clone $start)->setTime(self::OPENING_HOUR, 0, 0);
            $closing = (clone $start)->setTime(self::CLOSING_HOUR, 0, 0);

            if ($start < $opening || $end > $closing) {
                return ['disponible' => false, 'raison' => 'Hors des horaires d\'ouverture.'];
            }

            $rules = $this->unavailabilityRuleRepository->findBy(['active' => true]);
            $constraints = $this->contraintePrestationRepository->findActive();
            
            // DispoPrestation: si une des prestations sélectionnées est suspendue ce jour,
            // le créneau n'est pas disponible.
            $suspensions = !empty($tarifIds)
                ? $this->dispoPrestationRepository->findSuspensionsPourDate($start, $tarifIds)
                : [];
            if (!empty($suspensions)) {
                return ['disponible' => false, 'raison' => 'Prestation indisponible (DispoPrestation) sur cette date.'];
            }

            // ContraintePrestation
            if ($this->isDayRestrictedByConstraint($start, $tarifIds, $constraints)) {
                return ['disponible' => false, 'raison' => 'Contrainte de prestation (jour interdit ou limite atteinte).'];
            }
            
            $reservations = $this->reservationRepository->findReservationsByDay($start);
            $blocks = [];
            foreach ($reservations as $res) {
                $rStart = (new DateTime($res->getDateStart()->format('Y-m-d H:i:s'), new DateTimeZone('Europe/Paris')));
                $rEnd = (new DateTime($res->getDateEnd()->format('Y-m-d H:i:s'), new DateTimeZone('Europe/Paris')));
                $roundedEnd = $this->roundUpToNextQuarter($rEnd);
                $blocks[] = [$rStart, $roundedEnd];
            }

            if ($this->isSlotAvailable($start, $end, $rules) && !$this->overlapsAnyBlock($start, $end, $blocks)) {
                return ['disponible' => true, 'raison' => 'Créneau disponible'];
            }

            return ['disponible' => false, 'raison' => 'Le créneau est indisponible (règle ou réservation).'];
        } catch (\Exception $e) {
            return ['disponible' => false, 'raison' => 'Erreur technique : ' . $e->getMessage()];
        }
    }

    private function isSlotAvailable(DateTime $start, DateTime $end, array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($this->overlapsRule($start, $end, $rule)) {
                return false;
            }
        }
        return true;
    }

    private function overlapsRule(DateTime $start, DateTime $end, UnavailabilityRule $rule): bool
    {
        $recurrence = json_decode($rule->getRecurrence(), true);
        if (!$recurrence) {
            $recurrence = ['type' => 'daily'];
        }

        // 1. Check Date Validity (Recurrence Logic)
        if (!$this->isDateAffectedByRecurrence($start, $recurrence)) {
            return false;
        }

        // 2. Check Time Overlap
        // Merge entity times if missing in JSON
        if (!isset($recurrence['timeStart']) && $rule->getTimeStart()) {
            $recurrence['timeStart'] = $rule->getTimeStart()->format('H:i');
        }
        if (!isset($recurrence['timeEnd']) && $rule->getTimeEnd()) {
            $recurrence['timeEnd'] = $rule->getTimeEnd()->format('H:i');
        }

        return $this->isTimeAffectedByRecurrence($start, $end, $recurrence);
    }

    private function isDateAffectedByRecurrence(DateTime $date, array $recurrence): bool
    {
        $type = $recurrence['type'] ?? 'once';
        
        // Ensure we work in the same timezone (Paris/UTC) as the slot date
        $tz = $date->getTimezone();

        // Global Range Check
        $start = isset($recurrence['startDate']) ? new DateTime($recurrence['startDate'], $tz) : null;
        $end = isset($recurrence['endDate']) ? new DateTime($recurrence['endDate'], $tz) : null;

        // We compare "Dates" (days), so we set everything to 00:00:00 in that timezone
        $checkDate = (clone $date)->setTime(0, 0, 0);
        
        if ($start) $start->setTime(0, 0, 0);
        if ($end) $end->setTime(23, 59, 59);

        // Fix logic: if recurrence has a start date, the checked date must be >= start date
        if ($start && $checkDate < $start) return false;
        // if recurrence has an end date, the checked date must be <= end date
        if ($end && $checkDate > $end) return false;

        // Specific Type Logic
        switch ($type) {
            case 'once':
                if ($start && $checkDate != $start) return false;
                return true;

            case 'daily':
                if (!empty($recurrence['daysOfWeek'])) {
                    return $this->matchesDaysOfWeek($checkDate, $recurrence['daysOfWeek']);
                }
                return true;

            case 'weekly':
                if (!empty($recurrence['daysOfWeek'])) {
                    return $this->matchesDaysOfWeek($checkDate, $recurrence['daysOfWeek']);
                }
                // Fallback: Same day of week as startDate
                if ($start) {
                    return $checkDate->format('N') === $start->format('N');
                }
                return false;

            case 'monthly':
                if ($start) {
                    return $checkDate->format('d') === $start->format('d');
                }
                return false;

            case 'yearly':
                if ($start) {
                    return $checkDate->format('m-d') === $start->format('m-d');
                }
                return false;
        }

        return false;
    }

    private function matchesDaysOfWeek(DateTime $date, array $daysOfWeek): bool
    {
        $day = $date->format('w'); // 0 (Sun) - 6 (Sat)
        foreach ($daysOfWeek as $d) {
            if ((string)$d === (string)$day) return true;
        }
        return false;
    }

    private function roundUpToNextQuarter(DateTime $dt): DateTime
    {
        $minutes = (int)$dt->format('i');
        $add = 15 - ($minutes % 15);
        if ($add === 0) {
            $add = 15;
        }
        $rounded = (clone $dt)->modify("+{$add} minutes");
        $rounded->setTime((int)$rounded->format('H'), (int)$rounded->format('i'), 0);
        return $rounded;
    }

    private function overlapsAnyBlock(DateTime $start, DateTime $end, array $blocks): bool
    {
        foreach ($blocks as [$bStart, $bEnd]) {
            if ($start < $bEnd && $bStart < $end) {
                return true;
            }
        }
        return false;
    }

    private function isTimeAffectedByRecurrence(DateTime $start, DateTime $end, array $recurrence): bool
    {
        $rTimeStartStr = $recurrence['timeStart'] ?? null;
        $rTimeEndStr = $recurrence['timeEnd'] ?? null;

        if (!$rTimeStartStr && !$rTimeEndStr) {
            // Whole day
            return true;
        }

        // Project rule times onto the slot's day
        $rStart = $rTimeStartStr ? (clone $start)->modify($rTimeStartStr) : (clone $start)->setTime(0, 0);
        $rEnd = $rTimeEndStr ? (clone $start)->modify($rTimeEndStr) : (clone $start)->setTime(23, 59, 59);

        // Debug overlap for Wednesday afternoon
        // if ($start->format('N') == 3 && $start->format('H') == 14) {
        //     // error_log("Checking Slot: " . $start->format('H:i') . " - " . $end->format('H:i') . " vs Rule: " . $rStart->format('H:i') . " - " . $rEnd->format('H:i'));
        // }

        // Strict Overlap Check
        // Two ranges [A,B] and [C,D] overlap if A < D and C < B.
        // Here [start, end] and [rStart, rEnd].
        // start < rEnd AND rStart < end
        return ($start < $rEnd && $rStart < $end);
    }

    private function isDayRestrictedByConstraint(DateTime $date, array $tarifIds, array $constraints): bool
    {
        if (empty($tarifIds)) {
            return false;
        }

        $dayMapping = [
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            7 => 'dimanche',
        ];
        $currentDayName = $dayMapping[$date->format('N')] ?? null;

        foreach ($constraints as $constraint) {
            $constraintTarifIds = $constraint->getTarifs()->map(fn($t) => $t->getId())->toArray();
            
            if (empty($constraintTarifIds)) {
                continue;
            }

            $intersect = array_intersect($tarifIds, $constraintTarifIds);
            if (empty($intersect)) {
                continue;
            }

            // 1. Check Forbidden Days
            $forbiddenDays = $constraint->getJoursInterdits();
            if ($forbiddenDays && in_array($currentDayName, $forbiddenDays)) {
                return true;
            }

            // 2. Check Max Per Day
            $limit = $constraint->getLimiteParJour();
            if ($limit !== null) {
                $count = $this->reservationRepository->countReservationsForTariffs($date, $constraintTarifIds);
                if ($count >= $limit) {
                    return true;
                }
            }
        }

        return false;
    }
}
