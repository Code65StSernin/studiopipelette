<?php

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class NewsletterController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private NewsletterSubscriberRepository $subscriberRepository,
    ) {
    }

    #[Route('/newsletter/subscribe', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $token = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('newsletter_subscribe', (string) $token))) {
            $this->addFlash('danger', 'Le formulaire a expiré, merci de réessayer.');
            return $this->redirectToRoute('app_home');
        }

        $email = trim((string) $request->request->get('email'));
        $nom = trim((string) $request->request->get('nom')) ?: null;

        if ($email === '') {
            $this->addFlash('danger', 'Merci de renseigner un email.');
            return $this->redirectToRoute('app_home');
        }

        $existing = $this->subscriberRepository->findOneBy(['email' => strtolower($email)]);
        if ($existing) {
            if (!$existing->isActive()) {
                $existing->setIsActive(true);
                $this->em->flush();
            }

            $this->addFlash('info', 'Cet email est déjà inscrit à la newsletter.');
            return $this->redirectToRoute('app_home');
        }

        $subscriber = new NewsletterSubscriber();
        $subscriber->setEmail($email);
        $subscriber->setNom($nom);
        $subscriber->setUnsubscribeToken(bin2hex(random_bytes(32)));

        $this->em->persist($subscriber);
        $this->em->flush();

        $this->addFlash('success', 'Merci, vous êtes maintenant inscrit(e) à la newsletter.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/newsletter/unsubscribe/{token}', name: 'newsletter_unsubscribe', methods: ['GET'])]
    public function unsubscribe(string $token): Response
    {
        $subscriber = $this->subscriberRepository->findOneBy(['unsubscribeToken' => $token]);

        if (!$subscriber) {
            return $this->render('newsletter/unsubscribe.html.twig', [
                'status' => 'error',
            ]);
        }

        $subscriber->setIsActive(false);
        $this->em->flush();

        return $this->render('newsletter/unsubscribe.html.twig', [
            'status' => 'success',
        ]);
    }
}
