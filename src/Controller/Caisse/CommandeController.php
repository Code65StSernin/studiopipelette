<?php

namespace App\Controller\Caisse;

use App\Entity\Order;
use App\Form\MondialRelayShipmentType;
use App\Repository\OrderRepository;
use App\Service\FacturePdfGenerator;
use App\Service\MondialRelayService;
use App\Service\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/activites/commandes')]
class CommandeController extends AbstractController
{
    public function __construct(
        private FacturePdfGenerator $pdfGenerator,
        private MondialRelayService $mondialRelayService,
        private OrderMailer $orderMailer,
    ) {
    }

    #[Route('/', name: 'app_caisse_commande_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.facture', 'f')
            ->orderBy('o.createdAt', 'DESC');

        if ($q) {
            $queryBuilder
                ->where('f.numero LIKE :q')
                ->orWhere('f.clientNom LIKE :q')
                ->orWhere('f.clientPrenom LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('caisse/commande/index.html.twig', [
            'commandes' => $pagination,
        ]);
    }

    #[Route('/{id}/print-relay-label', name: 'app_caisse_commande_print_relay_label', methods: ['GET'])]
    public function printRelayLabel(Order $order): Response
    {
        if (!$this->mondialRelayService->hasCompleteShippingData($order) || !$order->getMondialRelayLabelUrl()) {
            return $this->redirectToRoute('app_caisse_commande_configure_shipment', ['id' => $order->getId()]);
        }

        $url = $order->getMondialRelayLabelUrl();

        // Si c'est une URL distante (cas courant avec Mondial Relay), on redirige vers celle-ci
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->redirect($url);
        }

        $response = new BinaryFileResponse($url);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'bordereau-mondial-relay-' . ($order->getFacture()?->getNumero() ?? $order->getId()) . '.pdf'
        );

        return $response;
    }

    #[Route('/{id}/configure-shipment', name: 'app_caisse_commande_configure_shipment', methods: ['GET', 'POST'])]
    public function configureShipment(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        // Logique similaire à l'admin pour pré-remplir les données
        if (!$order->getMondialRelayRecipientFirstName()) {
            $order->setMondialRelayRecipientFirstName($order->getUser()?->getPrenom() ?? $order->getFacture()?->getClientPrenom());
        }
        if (!$order->getMondialRelayRecipientLastName()) {
            $order->setMondialRelayRecipientLastName($order->getUser()?->getNom() ?? $order->getFacture()?->getClientNom());
        }
        if (!$order->getMondialRelayContentDescription()) {
            $order->setMondialRelayContentDescription('Bougies décoratives');
        }
        if ($order->getMondialRelayParcelsCount() === null) {
            $order->setMondialRelayParcelsCount(1);
        }
        if ($order->getMondialRelayContentValueCents() === null) {
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
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $labelUrl = $this->mondialRelayService->createShipmentAndGetLabel($order);

            if ($labelUrl) {
                $order->setMondialRelayLabelUrl($labelUrl);
                $em->flush();
                return $this->redirect($labelUrl);
            }

            $this->addFlash('danger', 'Impossible de générer le bordereau Mondial Relay. Vérifiez les informations.');
            return $this->redirectToRoute('app_caisse_commande_index');
        }

        return $this->render('caisse/commande/configure_shipment.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/ship', name: 'app_caisse_commande_ship', methods: ['POST'])]
    public function shipOrder(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('ship'.$order->getId(), $request->request->get('_token'))) {
            if (!$order->getMondialRelayShipmentNumber()) {
                $this->addFlash('warning', 'Aucun numéro d\'expédition Mondial Relay n\'est associé à cette commande.');
            } else {
                if (!$order->isShipped()) {
                    $order->setIsShipped(true);
                    $em->flush();
                    $this->orderMailer->sendShipmentNotification($order);
                    $this->addFlash('success', 'La commande a été marquée comme expédiée et le client notifié.');
                }
            }
        }
        return $this->redirectToRoute('app_caisse_commande_index');
    }

    #[Route('/{id}/invoice', name: 'app_caisse_commande_view_invoice', methods: ['GET'])]
    public function viewInvoice(Order $order): Response
    {
        $facture = $order->getFacture();
        if (!$facture) {
            $this->addFlash('warning', 'Aucune facture associée.');
            return $this->redirectToRoute('app_caisse_commande_index');
        }

        $pdfContent = $this->pdfGenerator->generate($facture);
        
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="facture-' . $facture->getNumero() . '.pdf"',
        ]);
    }
}
