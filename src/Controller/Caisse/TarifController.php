<?php

namespace App\Controller\Caisse;

use App\Entity\Tarif;
use App\Form\TarifType;
use App\Repository\TarifRepository;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/tarifs')]
class TarifController extends AbstractController
{
    public function __construct(private PictureService $pictureService)
    {
    }

    #[Route('/', name: 'app_caisse_tarif_index', methods: ['GET'])]
    public function index(TarifRepository $tarifRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $tarifRepository->createQueryBuilder('t')
            ->orderBy('t.nom', 'ASC');

        if ($q) {
            $queryBuilder->andWhere('t.nom LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('caisse/tarif/index.html.twig', [
            'tarifs' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_tarif_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tarif = new Tarif();
        $form = $this->createForm(TarifType::class, $tarif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle Image Upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile instanceof UploadedFile) {
                $filename = $this->pictureService->add($imageFile, '/tarifs', 200, 200);
                $tarif->setImage($filename);
            }

            $entityManager->persist($tarif);
            $entityManager->flush();

            $this->addFlash('success', 'Le tarif a bien été créé.');
            return $this->redirectToRoute('app_caisse_tarif_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/tarif/new.html.twig', [
            'tarif' => $tarif,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_tarif_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tarif $tarif, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TarifType::class, $tarif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle Image Upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile instanceof UploadedFile) {
                // Optionally delete old image if needed, but PictureService generates unique names
                $filename = $this->pictureService->add($imageFile, '/tarifs', 200, 200);
                $tarif->setImage($filename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le tarif a bien été modifié.');
            return $this->redirectToRoute('app_caisse_tarif_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/tarif/edit.html.twig', [
            'tarif' => $tarif,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_tarif_delete', methods: ['POST'])]
    public function delete(Request $request, Tarif $tarif, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tarif->getId(), $request->request->get('_token'))) {
            // TODO: Delete image file if necessary
            $entityManager->remove($tarif);
            $entityManager->flush();
            $this->addFlash('success', 'Le tarif a bien été supprimé.');
        }

        return $this->redirectToRoute('app_caisse_tarif_index', [], Response::HTTP_SEE_OTHER);
    }
}
