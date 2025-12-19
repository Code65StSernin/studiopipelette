<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Psr\Log\LoggerInterface;
use App\Service\SocieteConfig;

class ContactController extends AbstractController
{
    public function __construct(
        private MailerInterface $mailer,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private LoggerInterface $logger,
        private SocieteConfig $societeConfig,
    ) {
    }

    #[Route('/contact/send', name: 'app_contact_send', methods: ['POST'])]
    public function send(Request $request): Response
    {
        $isAjax = $request->isXmlHttpRequest();
        
        $data = [
            'prenom'    => trim((string) $request->request->get('prenom')),
            'nom'       => trim((string) $request->request->get('nom')),
            'telephone' => trim((string) $request->request->get('telephone')),
            // "email" est un nom réservé dans le contexte des TemplatedEmail,
            // on le renomme pour éviter le conflit.
            'fromEmail' => trim((string) $request->request->get('email')),
            'sujet'     => trim((string) $request->request->get('sujet')),
            'message'   => trim((string) $request->request->get('message')),
        ];

        $referer = $request->headers->get('referer') ?: $this->generateUrl('app_home');

        // CSRF
        $csrfToken = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('contact_form', $csrfToken))) {
            $errorMessage = 'Le formulaire a expiré, merci de réessayer.';
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => $errorMessage], 400);
            }
            $this->addFlash('danger', $errorMessage);
            return $this->redirect($referer);
        }

        // Validation basique des champs obligatoires
        foreach ($data as $key => $value) {
            if ($value === '') {
                $errorMessage = 'Tous les champs du formulaire de contact sont obligatoires.';
                if ($isAjax) {
                    return new JsonResponse(['success' => false, 'message' => $errorMessage], 400);
                }
                $this->addFlash('danger', $errorMessage);
                return $this->redirect($referer);
            }
        }

        if (!filter_var($data['fromEmail'], FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'L\'adresse email saisie n\'est pas valide.';
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => $errorMessage], 400);
            }
            $this->addFlash('danger', $errorMessage);
            return $this->redirect($referer);
        }

        // Vérification du captcha
        $a = (int) $request->request->get('captcha_a');
        $b = (int) $request->request->get('captcha_b');
        $result = (int) $request->request->get('captcha_result');

        if (!$a || !$b || !$result) {
            $this->logger->warning('Captcha incomplet dans le formulaire de contact', [
                'captcha_a' => $a,
                'captcha_b' => $b,
                'captcha_result' => $result,
            ]);
            $errorMessage = 'Le calcul de vérification est incomplet. Merci de réessayer.';
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => $errorMessage], 400);
            }
            $this->addFlash('danger', $errorMessage);
            return $this->redirect($referer);
        }

        if ($a + $b !== $result) {
            $this->logger->warning('Captcha incorrect dans le formulaire de contact', [
                'captcha_a' => $a,
                'captcha_b' => $b,
                'captcha_result' => $result,
                'expected' => $a + $b,
            ]);
            $errorMessage = 'Le résultat du calcul de vérification est incorrect. Merci de réessayer.';
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => $errorMessage], 400);
            }
            $this->addFlash('danger', $errorMessage);
            return $this->redirect($referer);
        }

        // Envoi de l'email
        try {
            $contactEmail = $this->societeConfig->getEmail() ?? 'contact@code65.fr';
            $fromEmail = $this->societeConfig->getSmtpFromEmail() ?? 'noreply@code65.fr';
            
            $email = (new TemplatedEmail())
                ->from($fromEmail)
                ->to($contactEmail)
                ->subject('Demande de contact So\'Sand : ' . $data['sujet'])
                ->htmlTemplate('emails/contact.html.twig')
                ->context([
                    'prenom'    => $data['prenom'],
                    'nom'       => $data['nom'],
                    'telephone' => $data['telephone'],
                    'sujet'     => $data['sujet'],
                    'message'   => $data['message'],
                    'fromEmail' => $data['fromEmail'],
                ]);

            $this->mailer->send($email);

            $successMessage = 'Votre demande de contact a bien été transmise. Merci, nous vous répondrons au plus vite.';
            if ($isAjax) {
                return new JsonResponse(['success' => true, 'message' => $successMessage]);
            }
            $this->addFlash('success', $successMessage);
        } catch (\Throwable $e) {
            // Logger l'erreur complète pour le débogage
            $this->logger->error('Erreur lors de l\'envoi du formulaire de contact', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            $errorMessage = 'Une erreur est survenue lors de l\'envoi du message. Merci de réessayer plus tard.';
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => $errorMessage], 500);
            }
            $this->addFlash('danger', $errorMessage);
        }

        return $this->redirect($referer);
    }
}
