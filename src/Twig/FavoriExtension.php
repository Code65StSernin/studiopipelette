<?php

namespace App\Twig;

use App\Service\FavoriService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class FavoriExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private FavoriService $favoriService
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'favoriService' => $this->favoriService,
        ];
    }
}

