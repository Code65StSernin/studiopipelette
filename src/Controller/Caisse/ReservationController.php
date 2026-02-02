<?php

namespace App\Controller\Caisse;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/activites/reservations')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_caisse_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $reservationRepository->createQueryBuilder('r')
            ->orderBy('r.dateStart', 'DESC');

        if ($q) {
            $queryBuilder
                ->where('r.clientName LIKE :q')
                ->orWhere('r.clientEmail LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('caisse/reservation/index.html.twig', [
            'reservations' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'La réservation a bien été créée.');

            return $this->redirectToRoute('app_caisse_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La réservation a bien été modifiée.');

            return $this->redirectToRoute('app_caisse_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'La réservation a été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}
