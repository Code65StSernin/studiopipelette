<?php

namespace App\Controller\Caisse;

use App\Entity\Offre;
use App\Form\OffreType;
use App\Repository\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/caisse/offres')]
class OffreController extends AbstractController
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    #[Route('/', name: 'app_caisse_offre_index', methods: ['GET'])]
    public function index(OffreRepository $offreRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $offreRepository->createQueryBuilder('o')
            ->orderBy('o.dateDebut', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('caisse/offre/index.html.twig', [
            'offres' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_offre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offre = new Offre();
        $form = $this->createForm(OffreType::class, $offre, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile instanceof UploadedFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/assets/img/offres',
                        $newFilename
                    );
                    $offre->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $entityManager->persist($offre);
            $entityManager->flush();

            $this->addFlash('success', 'L\'offre a bien été créée.');
            return $this->redirectToRoute('app_caisse_offre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/offre/new.html.twig', [
            'offre' => $offre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_offre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OffreType::class, $offre, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile instanceof UploadedFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/assets/img/offres',
                        $newFilename
                    );
                    
                    // Delete old image if exists
                    if ($offre->getImage()) {
                        $oldFile = $this->getParameter('kernel.project_dir') . '/public/assets/img/offres/' . $offre->getImage();
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }

                    $offre->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'L\'offre a bien été modifiée.');
            return $this->redirectToRoute('app_caisse_offre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/offre/edit.html.twig', [
            'offre' => $offre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_offre_delete', methods: ['POST'])]
    public function delete(Request $request, Offre $offre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$offre->getId(), $request->request->get('_token'))) {
            // Delete image if exists
            if ($offre->getImage()) {
                $oldFile = $this->getParameter('kernel.project_dir') . '/public/assets/img/offres/' . $offre->getImage();
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            $entityManager->remove($offre);
            $entityManager->flush();
            $this->addFlash('success', 'L\'offre a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_offre_index', [], Response::HTTP_SEE_OTHER);
    }
}
