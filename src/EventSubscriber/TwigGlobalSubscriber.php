<?php

namespace App\EventSubscriber;

use App\Repository\CategorieRepository;
use App\Repository\SousCategorieRepository;
use App\Service\SocieteConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class TwigGlobalSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Environment $twig,
        private CategorieRepository $categorieRepository,
        private SousCategorieRepository $sousCategorieRepository,
        private SocieteConfig $societeConfig,
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Injecter les catégories et sous-catégories dans toutes les vues Twig
        $this->twig->addGlobal('categories', $this->categorieRepository->findAll());
        $this->twig->addGlobal('sousCategories', $this->sousCategorieRepository->findAll());
        // Injecter la configuration de la société
        $this->twig->addGlobal('societe', $this->societeConfig);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}

