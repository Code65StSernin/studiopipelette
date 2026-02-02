<?php

namespace App\Controller\Caisse;

use App\Entity\Code;
use App\Form\CodeType;
use App\Repository\CodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/codes')]
class CodeController extends AbstractController
{
    #[Route('/', name: 'app_caisse_code_index', methods: ['GET'])]
    public function index(CodeRepository $codeRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $codeRepository->createQueryBuilder('c')
            ->orderBy('c.dateDebut', 'DESC');

        if ($q) {
            $queryBuilder->andWhere('c.code LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('caisse/code/index.html.twig', [
            'codes' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_code_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $code = new Code();
        $form = $this->createForm(CodeType::class, $code);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($code);
            $entityManager->flush();

            $this->addFlash('success', 'Le code promo a bien été créé.');
            return $this->redirectToRoute('app_caisse_code_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/code/new.html.twig', [
            'code' => $code,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_code_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Code $code, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CodeType::class, $code);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le code promo a bien été modifié.');
            return $this->redirectToRoute('app_caisse_code_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/code/edit.html.twig', [
            'code' => $code,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_code_delete', methods: ['POST'])]
    public function delete(Request $request, Code $code, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $code->getId(), $request->request->get('_token'))) {
            $entityManager->remove($code);
            $entityManager->flush();
            $this->addFlash('success', 'Le code promo a bien été supprimé.');
        }

        return $this->redirectToRoute('app_caisse_code_index', [], Response::HTTP_SEE_OTHER);
    }
}
