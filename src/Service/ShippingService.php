<?php

namespace App\Service;

use App\Entity\Code;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\TarifLettreSuivieRepository;
use App\Repository\TarifMondialRelayRepository;

class ShippingService
{
    public function __construct(
        private SocieteConfig $societeConfig,
        private TarifLettreSuivieRepository $tarifLettreSuivieRepository,
        private TarifMondialRelayRepository $tarifMondialRelayRepository
    ) {
    }

    public function calculateShippingCost(iterable $items, string $shippingMode, ?Code $promoCode = null): float
    {
        // 1. Vérifier si un code promo offre les frais de port
        if ($promoCode && $promoCode->isFraisPortOfferts()) {
            return 0.0;
        }

        // 2. Calculer le poids total
        $totalWeight = 0;
        foreach ($items as $item) {
            // Support both PanierLigne and LigneVente/Order items if they have similar structure
            // Assuming PanierLigne here which has getArticle()
            if (method_exists($item, 'getArticle')) {
                $article = $item->getArticle();
                $weight = $article->getPoids() ?? 0;
                $quantity = $item->getQuantite();
                $totalWeight += $weight * $quantity;
            }
        }

        // 3. Calculer le coût selon le mode
        if ($shippingMode === Order::SHIPPING_MODE_LETTRE_SUIVIE) {
            if (!$this->societeConfig->isEnableLettreSuivie()) {
                return 0.0; // Or throw exception? Default to 0 if not enabled but requested seems safe or fallback
            }
            return $this->getTarifLettreSuivie($totalWeight);
        }

        if ($shippingMode === Order::SHIPPING_MODE_RELAIS) {
            if (!$this->societeConfig->isEnableMondialRelay()) {
                return 0.0;
            }
            return $this->getTarifMondialRelay($totalWeight);
        }

        if ($shippingMode === Order::SHIPPING_MODE_DOMICILE) {
            return 5.90;
        }

        return 0.0;
    }

    private function getTarifLettreSuivie(int $weight): float
    {
        $tarif = $this->tarifLettreSuivieRepository->createQueryBuilder('t')
            ->where('t.poids >= :weight')
            ->setParameter('weight', $weight)
            ->orderBy('t.poids', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($tarif) {
            return $tarif->getTarif();
        }

        // Si plus lourd que le max, on prend le max (ou on pourrait refuser)
        $maxTarif = $this->tarifLettreSuivieRepository->findOneBy([], ['poids' => 'DESC']);
        return $maxTarif ? $maxTarif->getTarif() : 0.0;
    }

    private function getTarifMondialRelay(int $weight): float
    {
        $tarif = $this->tarifMondialRelayRepository->createQueryBuilder('t')
            ->where('t.poids >= :weight')
            ->setParameter('weight', $weight)
            ->orderBy('t.poids', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($tarif) {
            return $tarif->getTarif();
        }

        $maxTarif = $this->tarifMondialRelayRepository->findOneBy([], ['poids' => 'DESC']);
        return $maxTarif ? $maxTarif->getTarif() : 0.0;
    }
}
