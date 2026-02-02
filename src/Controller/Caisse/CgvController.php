<?php

namespace App\Controller\Caisse;

use App\Entity\Cgv;
use App\Form\CgvType;
use App\Repository\CgvRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/mentions-legales/cgv')]
class CgvController extends AbstractController
{
    #[Route('/', name: 'app_caisse_cgv_index', methods: ['GET'])]
    public function index(CgvRepository $cgvRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $cgvRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/cgv/index.html.twig', [
            'cgvs' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_cgv_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cgv = new Cgv();
        $form = $this->createForm(CgvType::class, $cgv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cgv);
            $entityManager->flush();

            $this->addFlash('success', 'La CGV a bien été créée.');

            return $this->redirectToRoute('app_caisse_cgv_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/cgv/new.html.twig', [
            'cgv' => $cgv,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_cgv_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cgv $cgv, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CgvType::class, $cgv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La CGV a bien été modifiée.');

            return $this->redirectToRoute('app_caisse_cgv_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/cgv/edit.html.twig', [
            'cgv' => $cgv,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_cgv_delete', methods: ['POST'])]
    public function delete(Request $request, Cgv $cgv, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cgv->getId(), $request->request->get('_token'))) {
            $entityManager->remove($cgv);
            $entityManager->flush();
            $this->addFlash('success', 'La CGV a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_cgv_index', [], Response::HTTP_SEE_OTHER);
    }
}
