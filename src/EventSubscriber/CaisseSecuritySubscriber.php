<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CaisseSecuritySubscriber implements EventSubscriberInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // On ne protège que les routes commençant par /caisse
        if (!str_starts_with($path, '/caisse')) {
            return;
        }

        // On exclut la route de login PIN pour éviter une boucle de redirection infinie
        if ($path === '/caisse/login-pin') {
            return;
        }

        // Si le PIN est déjà validé en session, on laisse passer
        if ($request->getSession()->get('caisse_pin_validated')) {
            return;
        }

        // Sinon, on sauvegarde l'URL cible et on redirige vers le login PIN
        $request->getSession()->set('caisse_redirect_target', $request->getUri());
        
        $response = new RedirectResponse($this->urlGenerator->generate('app_caisse_login_pin'));
        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
