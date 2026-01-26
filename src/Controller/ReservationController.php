<?php
namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationClientType;
use App\Repository\TarifRepository;
use App\Service\CreneauFinderService;
use App\Service\DisponibiliteService;
use App\Service\SocieteConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ReservationRepository;

class ReservationController extends AbstractController
{
    #[Route('/reservation', name: 'app_reservation')]
    public function index(TarifRepository $tarifRepository): Response
    {
        $tarifs = $tarifRepository->findAll();

        return $this->render('reservation/index.html.twig', [
            'tarifs' => $tarifs,
        ]);
    }

    #[Route('/reservation/creneaux', name: 'app_reservation_creneaux', methods: ['POST'])]
    public function creneaux(Request $request, CreneauFinderService $creneauFinderService, TarifRepository $tarifRepository): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $all = $request->request->all();
$prestationsData = $all['prestations'] ?? [];
        if (is_string($prestationsData)) {
            $prestationsIds = !empty($prestationsData) ? explode(',', $prestationsData) : [];
        } else {
            $prestationsIds = $prestationsData;
        }

        if (empty($prestationsIds)) {
            $this->addFlash('error', 'Veuillez sélectionner au moins une prestation.');
            return $this->redirectToRoute('app_reservation');
        }

        // Récupérer les tarifs sélectionnés
        $tarifs = $tarifRepository->findBy(['id' => $prestationsIds]);
        if (empty($tarifs)) {
            $this->addFlash('error', 'Les prestations sélectionnées sont invalides.');
            return $this->redirectToRoute('app_reservation');
        }

        // Calculer la durée totale
        $dureeTotale = array_sum(array_map(fn($t) => $t->getDureeMinutes(), $tarifs));

        // Trouver les créneaux disponibles
        $creneaux = $creneauFinderService->trouverProchainsCreneauxDisponibles($prestationsIds);

        if (empty($creneaux)) {
            $this->addFlash('warning', 'Aucun créneau disponible pour les prestations sélectionnées. Veuillez réessayer plus tard ou contacter directement l\'établissement.');
            return $this->redirectToRoute('app_reservation');
        }

        // Vider l'ancienne proposition de créneau avant d'afficher la page
        $request->getSession()->remove('creneau_propose');

        return $this->render('reservation/creneaux.html.twig', [
            'tarifs' => $tarifs,
            'prestationsIds' => $prestationsIds,
            'creneaux' => $creneaux,
            'dureeTotale' => $dureeTotale,
            'creneauPropose' => null, // S'assurer que la variable est nulle
        ]);
    }

    #[Route('/reservation/confirmer', name: 'app_reservation_confirmer', methods: ['POST'])]
    public function confirmer(
        Request $request, 
        TarifRepository $tarifRepository, 
        CreneauFinderService $creneauFinderService, 
        EntityManagerInterface $em,
        MailerInterface $mailer,
        SocieteConfig $societeConfig
    ): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $all = $request->request->all();
        $creneauSelectionne = $all['creneau'] ?? null;
        $prestationsIdsString = $all['prestations'] ?? '';
        $prestationsIds = !empty($prestationsIdsString) ? explode(',', $prestationsIdsString) : [];

        if (!$creneauSelectionne || empty($prestationsIds)) {
            $this->addFlash('error', 'Une erreur est survenue. Veuillez réessayer.');
            return $this->redirectToRoute('app_reservation');
        }

        // Sécurisation : recalculer la durée et vérifier la disponibilité
        $tarifs = $tarifRepository->findBy(['id' => $prestationsIds]);
        if (empty($tarifs)) {
            $this->addFlash('error', 'Les prestations sélectionnées sont invalides.');
            return $this->redirectToRoute('app_reservation');
        }
        $dureeTotale = array_sum(array_map(fn($t) => $t->getDureeMinutes(), $tarifs));
        $totalPrice = array_sum(array_map(fn($t) => $t->getTarif(), $tarifs));

        list($date, $heure) = explode('_', $creneauSelectionne);

        $verification = $creneauFinderService->estCreneauDisponible($date, $heure, $dureeTotale, $prestationsIds);

        if (!$verification['disponible']) {
            $this->addFlash('error', 'Le créneau que vous avez sélectionné n\'est plus disponible. Raison : ' . $verification['raison'] . '. Veuillez en choisir un autre.');
            
            // Rediriger vers la page des créneaux en repostant les données nécessaires
            $formParams = ['prestations' => $prestationsIdsString];
            return $this->redirectToRoute('app_reservation_creneaux', $formParams, 307);
        }

        $reservation = new Reservation();
        $form = $this->createForm(ReservationClientType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateStart = new \DateTime("{$date} {$heure}", new \DateTimeZone('Europe/Paris'));
            $dateEnd = (clone $dateStart)->modify("+{$dureeTotale} minutes");

            $reservation->setDateStart($dateStart);
            $reservation->setDateEnd($dateEnd);

            foreach ($tarifs as $tarif) {
                $reservation->addPrestation($tarif);
            }

            $reservation->setTotalPrice($totalPrice);

            $em->persist($reservation);
            $em->flush();

            // Envoi de l'email de confirmation
            $fromEmail = $societeConfig->getSmtpFromEmail() ?? 'noreply@code65.fr';
            $societeNom = $societeConfig->getNom() ?? 'Studio Pipelette';

            $email = (new TemplatedEmail())
                ->from(new Address($fromEmail, $societeNom))
                ->to($reservation->getClientEmail())
                ->subject('Confirmation de votre réservation - ' . $societeNom)
                ->htmlTemplate('emails/reservation_confirmation.html.twig')
                ->context([
                    'reservation' => $reservation,
                ]);

            $mailer->send($email);

            // Envoi de l'email de notification à l'administrateur (propriétaire du site)
            $adminEmailAddr = $societeConfig->getEmail();
            if ($adminEmailAddr) {
                try {
                    $adminEmail = (new TemplatedEmail())
                        ->from(new Address($fromEmail, $societeNom))
                        ->to($adminEmailAddr)
                        ->subject('Nouvelle réservation - ' . $reservation->getClientName())
                        ->htmlTemplate('emails/admin_reservation_notification.html.twig')
                        ->context([
                            'reservation' => $reservation,
                        ]);

                    $mailer->send($adminEmail);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Erreur envoi email admin: ' . $e->getMessage());
                }
            }

            $this->addFlash('success', "Votre réservation pour le {$date} à {$heure} est confirmée !");
            return $this->redirectToRoute('app_home');
        }

        return $this->render('reservation/confirmation.html.twig', [
            'form' => $form->createView(),
            'date' => $date,
            'heure' => $heure,
            'duree' => $dureeTotale,
            'tarifs' => $tarifs,
            'prestationsIds' => $prestationsIds,
            'totalPrice' => $totalPrice,
        ]);
    }

    #[Route('/reservation/proposer', name: 'app_reservation_proposer_creneau', methods: ['POST'])]
    public function proposerCreneau(Request $request, CreneauFinderService $creneauFinderService, TarifRepository $tarifRepository): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $all = $request->request->all();
        $prestationsIdsString = $all['prestations'] ?? '';
$prestationsIds = !empty($prestationsIdsString) ? explode(',', $prestationsIdsString) : [];
        $dateProposition = $all['date_proposition'] ?? null;
        $heureProposition = $all['heure_proposition'] ?? null;

        if (empty($prestationsIds) || !$dateProposition || !$heureProposition) {
            $this->addFlash('error', 'Veuillez sélectionner des prestations et proposer une date et une heure.');
            return $this->redirectToRoute('app_reservation');
        }

        $tarifs = $tarifRepository->findBy(['id' => $prestationsIds]);
        if (empty($tarifs)) {
            $this->addFlash('error', 'Les prestations sélectionnées sont invalides.');
            return $this->redirectToRoute('app_reservation');
        }

        $dureeTotale = array_sum(array_map(fn($t) => $t->getDureeMinutes(), $tarifs));

        $verification = $creneauFinderService->estCreneauDisponible($dateProposition, $heureProposition, $dureeTotale, $prestationsIds);

        $creneauPropose = null;
        if ($verification['disponible']) {
            $this->addFlash('success', 'Le créneau que vous avez proposé est disponible ! Vous pouvez maintenant le sélectionner et confirmer votre réservation.');
            
            $creneauPropose = [
                'date' => $dateProposition,
                'heure' => $heureProposition,
                'duree' => $dureeTotale,
            ];
            // Stocker le créneau proposé dans la session pour une utilisation ultérieure
            $request->getSession()->set('creneau_propose', $creneauPropose);
        } else {
            $this->addFlash('error', 'La prestation n\'est pas possible sur cette date et cette heure.');
            // Nettoyer la session si un ancien créneau y était
            $request->getSession()->remove('creneau_propose');
        }

        // Re-générer la page des créneaux avec le message flash
        $creneaux = $creneauFinderService->trouverProchainsCreneauxDisponibles($prestationsIds);
        return $this->render('reservation/creneaux.html.twig', [
            'tarifs' => $tarifs,
            'prestationsIds' => $prestationsIds,
            'creneaux' => $creneaux,
            'dureeTotale' => $dureeTotale,
            'creneauPropose' => $creneauPropose,
        ]);
    }

    #[Route('/compte/reservations', name: 'app_mes_reservations')]
    public function mesReservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservations = $reservationRepository->findByClientEmail($user->getEmail());

        return $this->render('reservation/mes_reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/compte/reservations/{id}/annuler', name: 'app_reservation_annuler', methods: ['POST'])]
    public function annulerReservation(
        int $id,
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        SocieteConfig $societeConfig
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = $reservationRepository->find($id);

        if (!$reservation || $reservation->getClientEmail() !== $user->getEmail()) {
            $this->addFlash('error', 'Réservation introuvable ou accès refusé.');
            return $this->redirectToRoute('app_mes_reservations');
        }

        if (!$this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_mes_reservations');
        }

        // Envoi de l'email d'annulation
        $fromEmail = $societeConfig->getSmtpFromEmail() ?? 'noreply@code65.fr';
        $societeNom = $societeConfig->getNom() ?? 'L\'écrin de beauté';

        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, $societeNom))
            ->to($reservation->getClientEmail())
            ->subject('Annulation de votre réservation - ' . $societeNom)
            ->htmlTemplate('emails/reservation_cancellation.html.twig')
            ->context([
                'reservation' => $reservation,
            ]);

        $mailer->send($email);

        // Envoi de l'email de notification d'annulation à l'administrateur
        $adminEmailAddr = $societeConfig->getEmail();
        if ($adminEmailAddr) {
            try {
                $adminEmail = (new TemplatedEmail())
                    ->from(new Address($fromEmail, $societeNom))
                    ->to($adminEmailAddr)
                    ->subject('Annulation de réservation - ' . $reservation->getClientName())
                    ->htmlTemplate('emails/admin_reservation_cancellation.html.twig')
                    ->context([
                        'reservation' => $reservation,
                    ]);

                $mailer->send($adminEmail);
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Erreur envoi email admin: ' . $e->getMessage());
            }
        }

        $em->remove($reservation);
        $em->flush();

        $this->addFlash('success', 'Votre réservation a été annulée avec succès.');

        return $this->redirectToRoute('app_mes_reservations');
    }
}
