<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\SocieteConfig;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password_request')]
    public function request(Request $request, UserRepository $userRepository, MailerInterface $mailer, EntityManagerInterface $entityManager, SocieteConfig $societeConfig): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Générer un token unique
                $resetToken = bin2hex(random_bytes(32));
                $user->setResetToken($resetToken);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

                $entityManager->flush();

                // Envoyer l'email
                $fromEmail = $societeConfig->getSmtpFromEmail() ?? 'noreply@code65.fr';
                $societeNom = $societeConfig->getNom() ?? 'So\'Sand';
                
                $email = (new TemplatedEmail())
                    ->from(new Address($fromEmail, $societeNom))
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->htmlTemplate('reset_password/email.html.twig')
                    ->context([
                        'resetToken' => $resetToken,
                        'user' => $user,
                    ]);

                $mailer->send($email);
            }

            // Toujours afficher le même message pour éviter l'énumération d'emails
            $message = 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.';
            
            // Si c'est une requête AJAX
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'message' => $message]);
            }
            
            $this->addFlash('success', $message);
            return $this->redirectToRoute('app_home');
        }

        return $this->render('reset_password/request.html.twig');
    }

    #[Route('/api/check-reset-token', name: 'app_check_reset_token', methods: ['POST'])]
    public function checkToken(Request $request, UserRepository $userRepository): JsonResponse
    {
        $token = $request->request->get('token');
        
        if (!$token) {
            return new JsonResponse(['valid' => false, 'message' => 'Token manquant.'], 400);
        }
        
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return new JsonResponse(['valid' => false, 'message' => 'Le lien de réinitialisation est invalide ou a expiré.']);
        }

        return new JsonResponse(['valid' => true]);
    }

    #[Route('/api/reset-password', name: 'app_reset_password_submit', methods: ['POST'])]
    public function resetSubmit(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $token = $request->request->get('token');
        $password = $request->request->get('password');
        $confirmPassword = $request->request->get('confirm_password');

        if (!$token || !$password || !$confirmPassword) {
            return new JsonResponse(['error' => 'Tous les champs sont obligatoires.'], 400);
        }

        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return new JsonResponse(['error' => 'Le lien de réinitialisation est invalide ou a expiré.'], 400);
        }

        if ($password !== $confirmPassword) {
            return new JsonResponse(['error' => 'Les mots de passe ne correspondent pas.'], 400);
        }

        // Hasher et enregistrer le nouveau mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.'
        ]);
    }
}

