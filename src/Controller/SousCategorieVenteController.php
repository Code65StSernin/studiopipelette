<?php

namespace App\Controller;

use App\Entity\SousCategorieVente;
use App\Form\SousCategorieVenteType;
use App\Repository\SousCategorieVenteRepository;
use App\Service\PictureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/sous-categories')]
class SousCategorieVenteController extends AbstractController
{
    #[Route('/', name: 'sous_categorie_vente_index', methods: ['GET'])]
    public function index(SousCategorieVenteRepository $sousCategorieVenteRepository): Response
    {
        return $this->render('caisse/sous_categorie/index.html.twig', [
            'sous_categorie_ventes' => $sousCategorieVenteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'sous_categorie_vente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SousCategorieVenteRepository $sousCategorieVenteRepository, PictureService $pictureService): Response
    {
        $sousCategorieVente = new SousCategorieVente();
        $form = $this->createForm(SousCategorieVenteType::class, $sousCategorieVente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $visuelType = $request->request->get('visuelType');

            if ($visuelType === 'image') {
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $fichier = $pictureService->add($imageFile, '/sous_categories', 300, 300);
                    $sousCategorieVente->setImage($fichier);
                }
                $sousCategorieVente->setCouleur(null);
            } else {
                $sousCategorieVente->setImage(null);
            }

            $sousCategorieVenteRepository->save($sousCategorieVente, true);

            $this->addFlash('success', 'La sous-catégorie a bien été créée.');

            return $this->redirectToRoute('sous_categorie_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/sous_categorie/new.html.twig', [
            'sous_categorie_vente' => $sousCategorieVente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'sous_categorie_vente_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SousCategorieVente $sousCategorieVente, SousCategorieVenteRepository $sousCategorieVenteRepository, PictureService $pictureService): Response
    {
        $form = $this->createForm(SousCategorieVenteType::class, $sousCategorieVente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $visuelType = $request->request->get('visuelType');

            if ($visuelType === 'image') {
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    if ($sousCategorieVente->getImage()) {
                        $pictureService->delete($sousCategorieVente->getImage(), '/sous_categories', 300, 300);
                    }
                    $fichier = $pictureService->add($imageFile, '/sous_categories', 300, 300);
                    $sousCategorieVente->setImage($fichier);
                }
                $sousCategorieVente->setCouleur(null);
            } else {
                if ($sousCategorieVente->getImage()) {
                    $pictureService->delete($sousCategorieVente->getImage(), '/sous_categories', 300, 300);
                    $sousCategorieVente->setImage(null);
                }
            }

            $sousCategorieVenteRepository->save($sousCategorieVente, true);

            $this->addFlash('success', 'La sous-catégorie a bien été modifiée.');

            return $this->redirectToRoute('sous_categorie_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/sous_categorie/edit.html.twig', [
            'sous_categorie_vente' => $sousCategorieVente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'sous_categorie_vente_delete', methods: ['POST'])]
    public function delete(Request $request, SousCategorieVente $sousCategorieVente, SousCategorieVenteRepository $sousCategorieVenteRepository, PictureService $pictureService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sousCategorieVente->getId(), $request->request->get('_token'))) {
            if ($sousCategorieVente->getImage()) {
                $pictureService->delete($sousCategorieVente->getImage(), '/sous_categories', 300, 300);
            }
            
            $sousCategorieVenteRepository->remove($sousCategorieVente, true);
            $this->addFlash('success', 'La sous-catégorie a été supprimée.');
        }

        return $this->redirectToRoute('sous_categorie_vente_index', [], Response::HTTP_SEE_OTHER);
    }
}
