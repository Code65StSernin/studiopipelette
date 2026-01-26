<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CaisseSecurityController extends AbstractController
{
    #[Route('/caisse/login-pin', name: 'app_caisse_login_pin')]
    public function loginPin(Request $request): Response
    {
        // Si déjà validé, on redirige (sauf si AJAX où on renvoie succès)
        if ($request->getSession()->get('caisse_pin_validated')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true]);
            }
            return $this->redirectToRoute('app_caisse');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $pin = $request->request->get('pin');
            
            // Code PIN en dur : 1234
            if ($pin === '1234') {
                $request->getSession()->set('caisse_pin_validated', true);
                
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
}
