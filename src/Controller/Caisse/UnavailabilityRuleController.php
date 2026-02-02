<?php

namespace App\Controller\Caisse;

use App\Entity\UnavailabilityRule;
use App\Form\UnavailabilityRuleType;
use App\Repository\UnavailabilityRuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/planning/indisponibilites')]
class UnavailabilityRuleController extends AbstractController
{
    #[Route('/', name: 'app_caisse_unavailability_rule_index', methods: ['GET'])]
    public function index(UnavailabilityRuleRepository $unavailabilityRuleRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $unavailabilityRuleRepository->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        if ($q) {
            $queryBuilder
                ->where('u.name LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/unavailability_rule/index.html.twig', [
            'unavailability_rules' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_unavailability_rule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $unavailabilityRule = new UnavailabilityRule();
        $form = $this->createForm(UnavailabilityRuleType::class, $unavailabilityRule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($unavailabilityRule);
            $entityManager->flush();

            $this->addFlash('success', 'L\'indisponibilité a bien été créée.');

            return $this->redirectToRoute('app_caisse_unavailability_rule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/unavailability_rule/new.html.twig', [
            'unavailability_rule' => $unavailabilityRule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_unavailability_rule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UnavailabilityRule $unavailabilityRule, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UnavailabilityRuleType::class, $unavailabilityRule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'indisponibilité a bien été modifiée.');

            return $this->redirectToRoute('app_caisse_unavailability_rule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/unavailability_rule/edit.html.twig', [
            'unavailability_rule' => $unavailabilityRule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_unavailability_rule_delete', methods: ['POST'])]
    public function delete(Request $request, UnavailabilityRule $unavailabilityRule, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$unavailabilityRule->getId(), $request->request->get('_token'))) {
            $entityManager->remove($unavailabilityRule);
            $entityManager->flush();
            $this->addFlash('success', 'L\'indisponibilité a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_unavailability_rule_index', [], Response::HTTP_SEE_OTHER);
    }
}
