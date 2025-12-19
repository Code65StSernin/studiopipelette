<?php

namespace App\Twig;

use App\Service\PanierService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class PanierExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private PanierService $panierService
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'panierService' => $this->panierService,
        ];
    }
}

