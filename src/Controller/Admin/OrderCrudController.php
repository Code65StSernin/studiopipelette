<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Service\FacturePdfGenerator;
use App\Service\MondialRelayService;
use App\Service\OrderMailer;
use App\Form\MondialRelayShipmentType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private FacturePdfGenerator $pdfGenerator,
        private AdminUrlGenerator $adminUrlGenerator,
        private MondialRelayService $mondialRelayService,
        private OrderMailer $orderMailer,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(10);
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action personnalisée pour le bordereau Mondial Relay
        $printRelayLabel = Action::new('printRelayLabel', 'Imprimer bordereau Mondial Relay', 'fa fa-print')
            ->linkToCrudAction('printRelayLabel')
            ->setCssClass('btn btn-info')
            // Afficher le bouton dès qu'une facture existe,
            // quel que soit le mode de livraison (relais ou domicile)
            ->displayIf(static function ($entity) {
                return $entity->getFacture() !== null;
            });

        // Action pour marquer l'expédition du colis et notifier le client
        $shipOrder = Action::new('shipOrder', 'Expédition du colis', 'fa fa-truck')
            ->linkToCrudAction('shipOrder')
            ->setCssClass('btn btn-success')
            ->displayIf(static function ($entity) {
                return $entity->getMondialRelayShipmentNumber() && !$entity->isShipped();
            });

        // Action pour visualiser la facture PDF associée à la commande
        $viewInvoice = Action::new('viewInvoice', 'Voir la facture PDF', 'fa fa-file-pdf')
            ->linkToCrudAction('viewInvoice')
            ->setCssClass('btn btn-danger')
            ->displayIf(static function ($entity) {
                return $entity->getFacture() !== null;
            });

        return $actions
            // Désactiver les actions de base
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::DETAIL)
            // Ajouter notre action personnalisée à la page d'index
            ->add(Crud::PAGE_INDEX, $printRelayLabel)
            ->add(Crud::PAGE_INDEX, $shipOrder)
            ->add(Crud::PAGE_INDEX, $viewInvoice);
    }

    public function configureFields(string $pageName): iterable
    {
        // Informations client
        yield TextField::new('facture.numero', 'N° Facture');
        yield TextField::new('facture.clientNom', 'Nom client');
        yield TextField::new('facture.clientPrenom', 'Prénom client');

        // Date de commande
        yield DateTimeField::new('createdAt', 'Date de commande')
            ->setFormat('short', 'short');

        // Détail des articles commandés (lignes de facture)
        yield CollectionField::new('facture.lignesFacture', 'Détail des articles')
            ->setTemplatePath('admin/fields/order_details.html.twig');

        // Montant total de la commande (hors frais de port)
        // correspond à amountProductsCents stocké en centimes
        yield MoneyField::new('amountProductsCents', 'Total (hors port)')
            ->setCurrency('EUR')
            ->setStoredAsCents(true);

        // Statut d'expédition
        yield BooleanField::new('isShipped', 'Expédié');
    }

    /**
     * Action pour générer et afficher le bordereau Mondial Relay.
     */
    public function printRelayLabel(AdminContext $context): Response
    {
        /** @var Order $order */
        $order = $context->getEntity()->getInstance();

        // Si les données d'expédition sont incomplètes, on redirige vers l'action EasyAdmin de configuration
        if (!$this->mondialRelayService->hasCompleteShippingData($order) || !$order->getMondialRelayLabelUrl()) {
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction('configureShipment')
                ->setEntityId($order->getId())
                ->generateUrl();

            return $this->redirect($url);
        }

        // Sinon, on affiche directement le bordereau déjà généré
        $response = new BinaryFileResponse($order->getMondialRelayLabelUrl());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'bordereau-mondial-relay-' . ($order->getFacture()?->getNumero() ?? $order->getId()) . '.pdf'
        );

        return $response;
    }

    /**
     * Action EasyAdmin pour saisir / compléter les infos d'expédition Mondial Relay.
     */
    public function configureShipment(AdminContext $context, EntityManagerInterface $em): Response
    {
        /** @var Order $order */
        $order = $context->getEntity()->getInstance();

        // Adresse client (adresse par défaut si elle existe)
        $user = $order->getUser();
        $customerAddress = null;
        if ($user) {
            $addresses = $user->getAddresses();
            $defaultAddress = null;
            foreach ($addresses as $addr) {
                if ($addr->isDefault()) {
                    $defaultAddress = $addr;
                    break;
                }
            }
            if (!$defaultAddress && count($addresses) > 0) {
                $first = $addresses->first();
                $defaultAddress = $first !== false ? $first : null;
            }

            if ($defaultAddress) {
                $customerAddress = $defaultAddress->getFullAddress();
            }
        }

        // Si pas d'adresse client et expédition en point relais,
        // on utilise au moins le CP + ville du point relais (contenus dans relayAddress)
        if (!$customerAddress && $order->getShippingMode() === 'relais' && $order->getRelayAddress()) {
            $customerAddress = $order->getRelayAddress();
        }

        // Adresse du point relais si applicable
        $relayAddress = null;
        if ($order->getShippingMode() === 'relais' && $order->getRelayAddress()) {
            $relayAddress = trim(($order->getRelayName() ?? '') . ' - ' . $order->getRelayAddress());
        }

        // Pré-remplissage de base (nom/prénom depuis la facture ou l'utilisateur)
        if (!$order->getMondialRelayRecipientFirstName()) {
            $order->setMondialRelayRecipientFirstName($order->getUser()?->getPrenom() ?? $order->getFacture()?->getClientPrenom());
        }
        if (!$order->getMondialRelayRecipientLastName()) {
            $order->setMondialRelayRecipientLastName($order->getUser()?->getNom() ?? $order->getFacture()?->getClientNom());
        }
        if (!$order->getMondialRelayContentDescription()) {
            $order->setMondialRelayContentDescription('Bougies décoratives');
        }

        // Pré-remplissage des données colis
        if ($order->getMondialRelayParcelsCount() === null) {
            $order->setMondialRelayParcelsCount(1); // 1 colis par défaut
        }
        if ($order->getMondialRelayContentValueCents() === null) {
            // Valeur du contenu = total des produits (hors frais de port)
            $order->setMondialRelayContentValueCents($order->getAmountProductsCents());
        }
        if ($order->getMondialRelayLengthCm() === null) {
            $order->setMondialRelayLengthCm(20);
        }
        if ($order->getMondialRelayWidthCm() === null) {
            $order->setMondialRelayWidthCm(15);
        }
        if ($order->getMondialRelayHeightCm() === null) {
            $order->setMondialRelayHeightCm(10);
        }

        $form = $this->createForm(MondialRelayShipmentType::class, $order);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde des infos Mondial Relay dans l'entité Order
            $em->flush();

            // Appel réel à l'API Mondial Relay (sandbox) pour générer l'étiquette
            $labelUrl = $this->mondialRelayService->createShipmentAndGetLabel($order);

            if ($labelUrl) {
                $order->setMondialRelayLabelUrl($labelUrl);
                $em->flush();

                // Redirection directe vers le PDF hébergé par Mondial Relay
                return $this->redirect($labelUrl);
            }

            $this->addFlash('danger', 'Impossible de générer le bordereau Mondial Relay. Vérifiez les informations saisies ou réessayez plus tard.');

            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();

            return $this->redirect($url);
        }

        return $this->render('admin/mondial_relay/form.html.twig', [
            'order'            => $order,
            'form'             => $form->createView(),
            'customer_address' => $customerAddress,
            'relay_address'    => $relayAddress,
        ]);
    }

    /**
     * Action EasyAdmin : marquer la commande comme expédiée et envoyer un email au client.
     */
    public function shipOrder(AdminContext $context, EntityManagerInterface $em): Response
    {
        /** @var Order $order */
        $order = $context->getEntity()->getInstance();

        if (!$order->getMondialRelayShipmentNumber()) {
            $this->addFlash('warning', 'Aucun numéro d\'expédition Mondial Relay n\'est associé à cette commande.');
        } else {
            if (!$order->isShipped()) {
                $order->setIsShipped(true);
                $em->flush();

                // Envoi de l'email d'expédition au client
                $this->orderMailer->sendShipmentNotification($order);

                $this->addFlash('success', 'La commande a été marquée comme expédiée et un email a été envoyé au client.');
            } else {
                $this->addFlash('info', 'Cette commande est déjà marquée comme expédiée.');
            }
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * Action EasyAdmin : visualiser la facture PDF associée à la commande.
     * Utile même si le compte utilisateur a été supprimé.
     */
    public function viewInvoice(AdminContext $context): Response
    {
        /** @var Order $order */
        $order = $context->getEntity()->getInstance();
        $facture = $order->getFacture();

        if (!$facture) {
            $this->addFlash('warning', 'Aucune facture n\'est associée à cette commande.');

            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();

            return $this->redirect($url);
        }

        // Générer (ou régénérer) le PDF de la facture à la volée
        $pdfPath = $this->pdfGenerator->generate($facture);

        $response = new BinaryFileResponse($pdfPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'facture-' . $facture->getNumero() . '.pdf'
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }
}