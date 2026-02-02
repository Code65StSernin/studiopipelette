<?php

namespace App\Controller\Caisse;

use App\Entity\BtoB;
use App\Form\PartenaireBtoBType;
use App\Repository\BtoBRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/btob/partenaires')]
class PartenaireBtoBController extends AbstractController
{
    #[Route('/', name: 'app_caisse_partenaire_btob_index', methods: ['GET'])]
    public function index(BtoBRepository $btoBRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $btoBRepository->createQueryBuilder('b')
            ->orderBy('b.nom', 'ASC');

        if ($q) {
            $queryBuilder
                ->where('b.nom LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/partenaire_btob/index.html.twig', [
            'partenaires' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_partenaire_btob_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $btoB = new BtoB();
        $form = $this->createForm(PartenaireBtoBType::class, $btoB);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($btoB);
            $entityManager->flush();

            $this->addFlash('success', 'Le partenaire BtoB a bien été créé.');

            return $this->redirectToRoute('app_caisse_partenaire_btob_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/partenaire_btob/new.html.twig', [
            'partenaire' => $btoB,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_partenaire_btob_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BtoB $btoB, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PartenaireBtoBType::class, $btoB);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le partenaire BtoB a bien été modifié.');

            return $this->redirectToRoute('app_caisse_partenaire_btob_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/partenaire_btob/edit.html.twig', [
            'partenaire' => $btoB,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_partenaire_btob_delete', methods: ['POST'])]
    public function delete(Request $request, BtoB $btoB, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$btoB->getId(), $request->request->get('_token'))) {
            $entityManager->remove($btoB);
            $entityManager->flush();
            $this->addFlash('success', 'Le partenaire BtoB a bien été supprimé.');
        }

        return $this->redirectToRoute('app_caisse_partenaire_btob_index', [], Response::HTTP_SEE_OTHER);
    }
}
