<?php

namespace App\Controller\Caisse;

use App\Entity\Faq;
use App\Form\FaqType;
use App\Repository\FaqRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/mentions-legales/faq')]
class FaqController extends AbstractController
{
    #[Route('/', name: 'app_caisse_faq_index', methods: ['GET'])]
    public function index(FaqRepository $faqRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $faqRepository->createQueryBuilder('f')
            ->orderBy('f.id', 'DESC');

        if ($q) {
            $queryBuilder
                ->where('f.question LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/faq/index.html.twig', [
            'faqs' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_faq_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $faq = new Faq();
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($faq);
            $entityManager->flush();

            $this->addFlash('success', 'La question FAQ a bien été créée.');

            return $this->redirectToRoute('app_caisse_faq_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/faq/new.html.twig', [
            'faq' => $faq,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_faq_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Faq $faq, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La question FAQ a bien été modifiée.');

            return $this->redirectToRoute('app_caisse_faq_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/faq/edit.html.twig', [
            'faq' => $faq,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_faq_delete', methods: ['POST'])]
    public function delete(Request $request, Faq $faq, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$faq->getId(), $request->request->get('_token'))) {
            $entityManager->remove($faq);
            $entityManager->flush();
            $this->addFlash('success', 'La question FAQ a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_faq_index', [], Response::HTTP_SEE_OTHER);
    }
}
