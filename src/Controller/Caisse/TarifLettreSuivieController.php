<?php

namespace App\Controller\Caisse;

use App\Entity\TarifLettreSuivie;
use App\Form\TarifLettreSuivieType;
use App\Repository\TarifLettreSuivieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/tables/tarif-lettre-suivie')]
class TarifLettreSuivieController extends AbstractController
{
    #[Route('/', name: 'app_caisse_tarif_lettre_suivie_index', methods: ['GET'])]
    public function index(TarifLettreSuivieRepository $tarifLettreSuivieRepository): Response
    {
        return $this->render('caisse/tarif_lettre_suivie/index.html.twig', [
            'tarif_lettre_suivies' => $tarifLettreSuivieRepository->findBy([], ['poids' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_caisse_tarif_lettre_suivie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tarifLettreSuivie = new TarifLettreSuivie();
        $form = $this->createForm(TarifLettreSuivieType::class, $tarifLettreSuivie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tarifLettreSuivie);
            $entityManager->flush();

            return $this->redirectToRoute('app_caisse_tarif_lettre_suivie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/tarif_lettre_suivie/new.html.twig', [
            'tarif_lettre_suivie' => $tarifLettreSuivie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_tarif_lettre_suivie_show', methods: ['GET'])]
    public function show(TarifLettreSuivie $tarifLettreSuivie): Response
    {
        return $this->render('caisse/tarif_lettre_suivie/show.html.twig', [
            'tarif_lettre_suivie' => $tarifLettreSuivie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_tarif_lettre_suivie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TarifLettreSuivie $tarifLettreSuivie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TarifLettreSuivieType::class, $tarifLettreSuivie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_caisse_tarif_lettre_suivie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/tarif_lettre_suivie/edit.html.twig', [
            'tarif_lettre_suivie' => $tarifLettreSuivie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_tarif_lettre_suivie_delete', methods: ['POST'])]
    public function delete(Request $request, TarifLettreSuivie $tarifLettreSuivie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tarifLettreSuivie->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tarifLettreSuivie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_caisse_tarif_lettre_suivie_index', [], Response::HTTP_SEE_OTHER);
    }
}
