<?php

namespace App\Controller;

use App\Entity\CategorieVente;
use App\Form\CategorieVenteType;
use App\Repository\CategorieVenteRepository;
use App\Service\PictureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/categories')]
class CategorieVenteController extends AbstractController
{
    #[Route('/', name: 'categorie_vente_index', methods: ['GET'])]
    public function index(CategorieVenteRepository $categorieVenteRepository): Response
    {
        return $this->render('caisse/categorie/index.html.twig', [
            'categorie_ventes' => $categorieVenteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'categorie_vente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CategorieVenteRepository $categorieVenteRepository, PictureService $pictureService): Response
    {
        $categorieVente = new CategorieVente();
        $form = $this->createForm(CategorieVenteType::class, $categorieVente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $visuelType = $request->request->get('visuelType');

            if ($visuelType === 'image') {
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $fichier = $pictureService->add($imageFile, '/categories', 100, 100);
                    $categorieVente->setImage($fichier);
                }
                // En mode image, on vide la couleur
                $categorieVente->setCouleur(null);
            } else {
                // En mode couleur, on s'assure qu'il n'y a pas d'image
                $categorieVente->setImage(null);
            }

            $categorieVenteRepository->save($categorieVente, true);

            $this->addFlash('success', 'La catégorie a bien été créée.');

            return $this->redirectToRoute('categorie_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/categorie/new.html.twig', [
            'categorie_vente' => $categorieVente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'categorie_vente_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategorieVente $categorieVente, CategorieVenteRepository $categorieVenteRepository, PictureService $pictureService): Response
    {
        $form = $this->createForm(CategorieVenteType::class, $categorieVente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $visuelType = $request->request->get('visuelType');

            if ($visuelType === 'image') {
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    // Suppression de l'ancienne image
                    if ($categorieVente->getImage()) {
                        $pictureService->delete($categorieVente->getImage(), '/categories', 100, 100);
                    }
                    $fichier = $pictureService->add($imageFile, '/categories', 100, 100);
                    $categorieVente->setImage($fichier);
                }
                $categorieVente->setCouleur(null);
            } else {
                // Suppression de l'image si on passe en mode couleur
                if ($categorieVente->getImage()) {
                    $pictureService->delete($categorieVente->getImage(), '/categories', 100, 100);
                    $categorieVente->setImage(null);
                }
            }

            $categorieVenteRepository->save($categorieVente, true);

            $this->addFlash('success', 'La catégorie a bien été modifiée.');

            return $this->redirectToRoute('categorie_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/categorie/edit.html.twig', [
            'categorie_vente' => $categorieVente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'categorie_vente_delete', methods: ['POST'])]
    public function delete(Request $request, CategorieVente $categorieVente, CategorieVenteRepository $categorieVenteRepository, PictureService $pictureService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categorieVente->getId(), $request->request->get('_token'))) {
            // Suppression de l'image si elle existe
            if ($categorieVente->getImage()) {
                $pictureService->delete($categorieVente->getImage(), '/categories', 100, 100);
            }
            
            $categorieVenteRepository->remove($categorieVente, true);
            $this->addFlash('success', 'La catégorie a été supprimée.');
        }

        return $this->redirectToRoute('categorie_vente_index', [], Response::HTTP_SEE_OTHER);
    }
}
