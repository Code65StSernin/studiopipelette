<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\SocieteConfig;

class CaisseSecurityController extends AbstractController
{
    #[Route('/caisse/login-pin', name: 'app_caisse_login_pin')]
    public function loginPin(Request $request, SocieteConfig $societeConfig): Response
    {
        // Si GET et déjà validé, on redirige (sauf si AJAX où on renvoie succès)
        if ($request->isMethod('GET') && $request->getSession()->get('caisse_pin_validated')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true]);
            }
            return $this->redirectToRoute('app_caisse');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            // Sécurise: on invalide systématiquement la session avant vérification
            $session = $request->getSession();
            $session->remove('caisse_pin_validated');
            $pin = trim((string)$request->request->get('pin'));
            
            // Vérification du code PIN via configuration
            if ($pin !== '' && hash_equals($societeConfig->getAdminPin(), $pin)) {
                $session->set('caisse_pin_validated', true);
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['success' => true]);
                }

                $targetUrl = $request->getSession()->get('caisse_redirect_target');
                $request->getSession()->remove('caisse_redirect_target');

                return $this->redirect($targetUrl ?? $this->generateUrl('app_caisse'));
            }

            $error = 'Code PIN incorrect';

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => $error], 400);
            }
        }

        return $this->render('security/login_pin.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/caisse/check-pin', name: 'app_caisse_check_pin', methods: ['POST'])]
    public function checkPin(Request $request, SocieteConfig $societeConfig): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $pin = isset($data['pin']) ? trim((string)$data['pin']) : '';

        if ($pin !== '' && hash_equals($societeConfig->getAdminPin(), $pin)) {
            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['success' => false], 403);
    }
}
