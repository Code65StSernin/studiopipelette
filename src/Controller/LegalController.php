<?php

namespace App\Controller;

use App\Repository\CgvRepository;
use App\Repository\PrivacyPolicyRepository;
use App\Repository\CookiePolicyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    #[Route('/cgv', name: 'app_cgv')]
    public function cgv(CgvRepository $cgvRepository): Response
    {
        $page = $cgvRepository->findOneBy([]); // Première et unique entrée

        return $this->render('legal/cgv.html.twig', [
            'page' => $page,
        ]);
    }

    #[Route('/politique-de-confidentialite', name: 'app_privacy_policy')]
    public function privacy(PrivacyPolicyRepository $privacyPolicyRepository): Response
    {
        $page = $privacyPolicyRepository->findOneBy([]);

        return $this->render('legal/privacy.html.twig', [
            'page' => $page,
        ]);
    }

    #[Route('/politique-de-cookies', name: 'app_cookie_policy')]
    public function cookies(CookiePolicyRepository $cookiePolicyRepository): Response
    {
        $page = $cookiePolicyRepository->findOneBy([]);

        return $this->render('legal/cookies.html.twig', [
            'page' => $page,
        ]);
    }
}

