<?php

namespace App\Controller\Caisse;

use App\Entity\TarifMondialRelay;
use App\Form\TarifMondialRelayType;
use App\Repository\TarifMondialRelayRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/tables/tarif-mondial-relay')]
class TarifMondialRelayController extends AbstractController
{
    #[Route('/', name: 'app_caisse_tarif_mondial_relay_index', methods: ['GET'])]
    public function index(TarifMondialRelayRepository $tarifMondialRelayRepository): Response
    {
        return $this->render('caisse/tarif_mondial_relay/index.html.twig', [
            'tarif_mondial_relays' => $tarifMondialRelayRepository->findBy([], ['poids' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_caisse_tarif_mondial_relay_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tarifMondialRelay = new TarifMondialRelay();
        $form = $this->createForm(TarifMondialRelayType::class, $tarifMondialRelay);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tarifMondialRelay);
            $entityManager->flush();

            return $this->redirectToRoute('app_caisse_tarif_mondial_relay_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/tarif_mondial_relay/new.html.twig', [
            'tarif_mondial_relay' => $tarifMondialRelay,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_tarif_mondial_relay_show', methods: ['GET'])]
    public function show(TarifMondialRelay $tarifMondialRelay): Response
    {
        return $this->render('caisse/tarif_mondial_relay/show.html.twig', [
            'tarif_mondial_relay' => $tarifMondialRelay,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_tarif_mondial_relay_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TarifMondialRelay $tarifMondialRelay, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TarifMondialRelayType::class, $tarifMondialRelay);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_caisse_tarif_mondial_relay_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/tarif_mondial_relay/edit.html.twig', [
            'tarif_mondial_relay' => $tarifMondialRelay,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_tarif_mondial_relay_delete', methods: ['POST'])]
    public function delete(Request $request, TarifMondialRelay $tarifMondialRelay, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tarifMondialRelay->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tarifMondialRelay);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_caisse_tarif_mondial_relay_index', [], Response::HTTP_SEE_OTHER);
    }
}
