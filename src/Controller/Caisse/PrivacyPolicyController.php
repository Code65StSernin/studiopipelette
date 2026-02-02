<?php

namespace App\Controller\Caisse;

use App\Entity\PrivacyPolicy;
use App\Form\PrivacyPolicyType;
use App\Repository\PrivacyPolicyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/mentions-legales/politique-confidentialite')]
class PrivacyPolicyController extends AbstractController
{
    #[Route('/', name: 'app_caisse_privacy_policy_index', methods: ['GET'])]
    public function index(PrivacyPolicyRepository $repository, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $repository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/privacy_policy/index.html.twig', [
            'privacy_policies' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_privacy_policy_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $policy = new PrivacyPolicy();
        $form = $this->createForm(PrivacyPolicyType::class, $policy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($policy);
            $entityManager->flush();

            $this->addFlash('success', 'La politique de confidentialité a bien été créée.');

            return $this->redirectToRoute('app_caisse_privacy_policy_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/privacy_policy/new.html.twig', [
            'privacy_policy' => $policy,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_privacy_policy_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PrivacyPolicy $policy, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PrivacyPolicyType::class, $policy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La politique de confidentialité a bien été modifiée.');

            return $this->redirectToRoute('app_caisse_privacy_policy_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/privacy_policy/edit.html.twig', [
            'privacy_policy' => $policy,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_privacy_policy_delete', methods: ['POST'])]
    public function delete(Request $request, PrivacyPolicy $policy, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$policy->getId(), $request->request->get('_token'))) {
            $entityManager->remove($policy);
            $entityManager->flush();
            $this->addFlash('success', 'La politique de confidentialité a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_privacy_policy_index', [], Response::HTTP_SEE_OTHER);
    }
}
