<?php
namespace App\Controller\Admin;

use App\Entity\Creneau;
use App\Entity\Reservation;
use App\Entity\UnavailabilityRule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminCalendarController extends AbstractController
{
    #[Route('/admin/calendar', name: 'admin_calendar')]
    public function index(): Response
    {
        return $this->render('admin/calendar.html.twig');
    }

    #[Route('/admin/api/creneaux', name: 'admin_api_creneaux')]
    public function apiCreneaux(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Accept start/end query params from FullCalendar to limit expansion window
        $startParam = $request->query->get('start');
        $endParam = $request->query->get('end');
        $rangeStart = $startParam ? new \DateTimeImmutable($startParam) : (new \DateTimeImmutable())->sub(new \DateInterval('P7D'));
        $rangeEnd = $endParam ? new \DateTimeImmutable($endParam) : (new \DateTimeImmutable())->add(new \DateInterval('P30D'));

        $events = [];

        // Creneaux (slots)
        $repo = $em->getRepository(Creneau::class);
        $qb = $repo->createQueryBuilder('c')
            ->andWhere('c.date BETWEEN :s AND :e')
            ->setParameter('s', $rangeStart->format('Y-m-d'))
            ->setParameter('e', $rangeEnd->format('Y-m-d'))
            ->orderBy('c.date', 'ASC');

        $items = $qb->getQuery()->getResult();
        foreach ($items as $c) {
            /** @var Creneau $c */
            $start = $c->getDate()->format('Y-m-d').'T'.$c->getStartTime()->format('H:i:s');
            $end = $c->getDate()->format('Y-m-d').'T'.$c->getEndTime()->format('H:i:s');
            $events[] = [
                'id' => 'slot-'.$c->getId(),
                'title' => $c->isBlocked() ? 'Bloqué' : 'Libre',
                'start' => $start,
                'end' => $end,
                'extendedProps' => ['slotKey' => $c->getSlotKey(), 'capacity' => $c->getCapacity(), 'type' => 'slot'],
                'color' => $c->isBlocked() ? '#d3573b' : '#a16877', // Orange (blocked) / Parme (free)
            ];
        }

        // Reservations
        $rRepo = $em->getRepository(Reservation::class);
        $rQb = $rRepo->createQueryBuilder('r')
            ->andWhere('r.dateStart < :e AND r.dateEnd > :s')
            ->setParameter('s', $rangeStart)
            ->setParameter('e', $rangeEnd)
            ->orderBy('r.dateStart', 'ASC');
        $reservations = $rQb->getQuery()->getResult();
        foreach ($reservations as $res) {
            /** @var Reservation $res */
            $start = $res->getDateStart()->format('Y-m-d\TH:i:s');
            $end = $res->getDateEnd()->format('Y-m-d\TH:i:s');
            $events[] = [
                'id' => 'res-'.$res->getId(),
                'title' => 'Réservé (#'.$res->getId().')',
                'start' => $start,
                'end' => $end,
                'extendedProps' => ['reservationId' => $res->getId(), 'type' => 'reservation'],
                'color' => '#d4807e', // Vieux rose
            ];
        }

        // Unavailability rules -> expand into events in the requested window
        $uRepo = $em->getRepository(UnavailabilityRule::class);
        $rules = $uRepo->findBy(['active' => true]);
        foreach ($rules as $rule) {
            $rec = $rule->getRecurrence();
            if (!$rec) continue;
            $parsed = json_decode($rec, true);
            if (!$parsed) continue;

            $type = $parsed['type'] ?? 'once';
            $ruleStart = isset($parsed['startDate']) ? new \DateTimeImmutable($parsed['startDate']) : null;
            $ruleEnd = isset($parsed['endDate']) ? new \DateTimeImmutable($parsed['endDate']) : null;
            $days = $parsed['daysOfWeek'] ?? null; // array of ints (0=Sun..6=Sat or 1..6)
            $ts = $parsed['timeStart'] ?? null; // 'HH:MM'
            $te = $parsed['timeEnd'] ?? null;

            // Fallback to entity times if missing in JSON
            if (!$ts && $rule->getTimeStart()) {
                $ts = $rule->getTimeStart()->format('H:i');
            }
            if (!$te && $rule->getTimeEnd()) {
                $te = $rule->getTimeEnd()->format('H:i');
            }

            // iterate days in window
            $period = new \DatePeriod($rangeStart, new \DateInterval('P1D'), $rangeEnd->add(new \DateInterval('P1D')));
            foreach ($period as $dt) {
                // respect rule start/end bounds
                if ($ruleStart && $dt < $ruleStart) continue;
                if ($ruleEnd && $dt > $ruleEnd) continue;

                $matches = false;
                if ($type === 'once') {
                    if ($ruleStart && $dt->format('Y-m-d') === $ruleStart->format('Y-m-d')) $matches = true;
                } elseif ($type === 'daily') {
                    $matches = true;
                } elseif ($type === 'weekly') {
                    if ($days && is_array($days)) {
                        // compare using PHP 'w' (0=Sun..6=Sat)
                        $w = (int)$dt->format('w');
                        if (in_array($w, $days, true) || in_array((string)$w, $days, true)) $matches = true;
                        // also support 1..7 mapping where Monday=1
                        if (!$matches) {
                            $n = (int)$dt->format('N'); // 1=Mon..7=Sun
                            $mapped = $n === 7 ? 0 : $n; // convert 7->0 for sunday mapping
                            if (in_array($mapped, $days, true)) $matches = true;
                        }
                    }
                } else {
                    // fallback: no-op (could add monthly/yearly later)
                }

                if ($matches) {
                    if ($ts && $te) {
                        // Clamp to view bounds (08:00 - 20:00) to ensure visibility in this specific calendar
                        $viewStartStr = '08:00';
                        $viewEndStr = '20:00';

                        // Check for overlap
                        if ($ts < $viewEndStr && $te > $viewStartStr) {
                            $rStart = $ts < $viewStartStr ? $viewStartStr : $ts;
                            $rEnd = $te > $viewEndStr ? $viewEndStr : $te;

                            $start = $dt->format('Y-m-d').'T'.$rStart.':00';
                            $end = $dt->format('Y-m-d').'T'.$rEnd.':00';
                            $events[] = [
                                'id' => 'rule-'.$rule->getId().'-'.$dt->format('Ymd'),
                                'title' => $rule->getName(),
                                'start' => $start,
                                'end' => $end,
                                'extendedProps' => ['ruleId' => $rule->getId(), 'type' => 'rule'],
                                'color' => '#FF0000', // Bright Red
                            ];
                        }
                    } else {
                        // all-day -> convert to full visible day (08:00 - 20:00)
                        $start = $dt->format('Y-m-d').'T08:00:00';
                        $end = $dt->format('Y-m-d').'T20:00:00';
                        $events[] = [
                            'id' => 'rule-'.$rule->getId().'-'.$dt->format('Ymd'),
                            'title' => $rule->getName(),
                            'start' => $start,
                            'end' => $end,
                            'extendedProps' => ['ruleId' => $rule->getId(), 'type' => 'rule'],
                            'color' => '#FF0000', // Bright Red
                        ];
                    }
                }
            }
        }

        return new JsonResponse($events);
    }
}
