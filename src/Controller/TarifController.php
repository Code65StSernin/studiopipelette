<?php
namespace App\Controller;

use App\Repository\TarifRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class TarifController extends AbstractController
{
    #[Route('/tarifs', name: 'app_soins_tarifs')]
    public function index(TarifRepository $tarifRepository): Response
    {
        $tarifs = $tarifRepository->findAll();

        return $this->render('tarifs/index.html.twig', [
            'tarifs' => $tarifs,
        ]);
    }
}
