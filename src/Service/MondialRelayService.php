<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Address;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\SocieteConfig;

/**
 * Service responsable de l'appel à l'API Mondial Relay
 * (Shipment API v2.7 / XML v1.0)
 */
class MondialRelayService
{
    private HttpClientInterface $httpClient;
    private SocieteConfig $societeConfig;
    private string $login = '';
    private string $password = '';
    private string $customerId = '';
    private string $apiUrl = '';

    public function __construct(
        HttpClientInterface $httpClient,
        SocieteConfig $societeConfig,
    ) {
        $this->httpClient = $httpClient;
        $this->societeConfig = $societeConfig;
        $this->login      = $societeConfig->getMondialRelayLogin() ?? '';
        $this->password   = $societeConfig->getMondialRelayPassword() ?? '';
        $this->customerId = $societeConfig->getMondialRelayCustomerId() ?? '';
        // URL API sandbox conservée ici pour l'instant
        $this->apiUrl     = 'https://connect-api-sandbox.mondialrelay.com/api/shipment';
    }

    /**
     * Vérifie si la commande contient les données nécessaires
     * pour appeler l'API Mondial Relay.
     */
    public function hasCompleteShippingData(Order $order): bool
    {
        if (!$order->getMondialRelayRecipientFirstName() || !$order->getMondialRelayRecipientLastName()) {
            return false;
        }

        if (!$order->getMondialRelayParcelsCount() || $order->getMondialRelayParcelsCount() <= 0) {
            return false;
        }

        if (!$order->getMondialRelayContentValueCents() || $order->getMondialRelayContentValueCents() <= 0) {
            return false;
        }

        if (!$order->getMondialRelayContentDescription()) {
            return false;
        }

        if (!$order->getMondialRelayLengthCm() || !$order->getMondialRelayWidthCm() || !$order->getMondialRelayHeightCm()) {
            return false;
        }

        if (!$order->getMondialRelayWeightKg() || $order->getMondialRelayWeightKg() <= 0) {
            return false;
        }

        // Pour les livraisons en point relais, on exige un relayId
        if ($order->getShippingMode() === 'relais' && !$order->getRelayId()) {
            return false;
        }

        return true;
    }

    /**
     * Appelle l'API Mondial Relay pour créer l'envoi et
     * retourne l'URL du PDF de l'étiquette (PdfUrl).
     *
     * - Met également à jour le numéro d'expédition sur l'Order
     *   via setMondialRelayShipmentNumber().
     * - NE fait PAS de flush() : à gérer par le contrôleur appelant.
     */
    public function createShipmentAndGetLabel(Order $order): ?string
    {
        // Sécurité minimale : si les identifiants ne sont pas configurés, on ne tente rien
        if (empty($this->login) || empty($this->password) || empty($this->customerId)) {
            return null;
        }

        // Conversion poids en grammes (min 10g)
        $weightGrams = max(10, (int) round(($order->getMondialRelayWeightKg() ?? 0) * 1000));

        // Valeur du contenu en euros (float avec 2 décimales)
        $valueEuros = ($order->getMondialRelayContentValueCents() ?? 0) / 100;

        // Mode de livraison Mondial Relay : 24R pour point relais, HOM pour domicile
        $deliveryMode = $order->getShippingMode() === 'relais' ? '24R' : 'HOM';
        $deliveryLocation = $order->getShippingMode() === 'relais' && $order->getRelayId()
            ? 'FR-' . $order->getRelayId()
            : '';

        // Mode de dépôt : CCC (dépôt en point relais/commerce du marchand)
        $collectionMode = 'CCC';

        // Récupération de l'adresse destinataire (utilisateur)
        $recipientAddress = $this->resolveRecipientAddress($order);

        // Construction du XML de requête
        $orderRef = $order->getFacture()?->getNumero() ?? ('CMD-' . $order->getId());
        $orderRef = substr($orderRef, 0, 15); // max 15 caractères

        $customerRef = 'CUS' . $order->getUser()?->getId();
        $customerRef = substr($customerRef, 0, 9);

        $xmlRequest = $this->buildXmlRequest(
            order: $order,
            orderRef: $orderRef,
            customerRef: $customerRef,
            parcelCount: $order->getMondialRelayParcelsCount() ?? 1,
            weightGrams: $weightGrams,
            valueEuros: $valueEuros,
            deliveryMode: $deliveryMode,
            deliveryLocation: $deliveryLocation,
            collectionMode: $collectionMode,
            recipient: $recipientAddress,
        );

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Accept' => 'application/xml',
                    'Content-Type' => 'text/xml',
                ],
                'body' => $xmlRequest,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                return null;
            }

            $content = $response->getContent(false);
            if (!$content) {
                return null;
            }

            $xml = @simplexml_load_string($content);
            if ($xml === false) {
                return null;
            }

            // Gestion du namespace Response
            $xml->registerXPathNamespace('r', 'http://www.example.org/Response');

            // Récupération de l'URL du PDF (OutputType = PdfUrl)
            $outputNodes = $xml->xpath('//r:Label/r:Output');
            if (!$outputNodes || empty($outputNodes[0])) {
                return null;
            }

            $labelUrl = trim((string) $outputNodes[0]);
            if ($labelUrl === '') {
                return null;
            }

            // Récupération éventuelle du numéro d'expédition
            $shipmentNodes = $xml->xpath('//r:Shipment');
            if ($shipmentNodes && isset($shipmentNodes[0]['ShipmentNumber'])) {
                $shipmentNumber = (string) $shipmentNodes[0]['ShipmentNumber'];
                if ($shipmentNumber !== '') {
                    $order->setMondialRelayShipmentNumber($shipmentNumber);
                }
            }

            return $labelUrl;
        } catch (\Throwable $e) {
            // En cas d'erreur réseau / parsing, on renvoie simplement null
            return null;
        }
    }

    /**
     * Résout l'adresse du destinataire à partir de l'Order.
     */
    private function resolveRecipientAddress(Order $order): array
    {
        $user = $order->getUser();
        $address = null;

        if ($user) {
            $addresses = $user->getAddresses();
            if ($addresses) {
                /** @var Address $addr */
                foreach ($addresses as $addr) {
                    if ($addr->isDefault()) {
                        $address = $addr;
                        break;
                    }
                }
                if (!$address && count($addresses) > 0) {
                    $first = $addresses->first();
                    $address = $first !== false ? $first : null;
                }
            }
        }

        $firstName = $order->getMondialRelayRecipientFirstName() ?? $user?->getPrenom() ?? '';
        $lastName  = $order->getMondialRelayRecipientLastName() ?? $user?->getNom() ?? '';

        if ($address instanceof Address) {
            $street = $address->getStreet();
            $houseNo = $address->getStreetNumber();
            $postalCode = $address->getPostalCode();
            $city = $address->getCity();
        } else {
            // Fallback très basique si aucune adresse n'est disponible
            $street = 'Adresse inconnue';
            $houseNo = '';
            $postalCode = '00000';
            $city = 'Ville';
        }

        return [
            'firstName'  => $firstName,
            'lastName'   => $lastName,
            'streetName' => $street,
            'houseNo'    => $houseNo,
            'postalCode' => $postalCode,
            'city'       => $city,
            'country'    => 'FR',
            'email'      => $order->getFacture()?->getClientEmail() ?? $user?->getEmail() ?? '',
            'phone'      => '',
        ];
    }

    /**
     * Construit la requête XML ShipmentCreationRequest.
     */
    private function buildXmlRequest(
        Order $order,
        string $orderRef,
        string $customerRef,
        int $parcelCount,
        int $weightGrams,
        float $valueEuros,
        string $deliveryMode,
        string $deliveryLocation,
        string $collectionMode,
        array $recipient,
    ): string {
        // Données expéditeur depuis l'entité Societe
        $adresse = $this->societeConfig->getAdresse() ?? '';
        $senderStreet = $adresse;
        $senderHouseNo = '';
        
        // Tentative de parsing de l'adresse (format simple: "65 Chemin des Mazes")
        if (preg_match('/^(\d+)\s+(.+)$/', $adresse, $matches)) {
            $senderHouseNo = $matches[1];
            $senderStreet = $matches[2];
        }
        
        $senderPostCode = $this->societeConfig->getCodePostal() ?? '07200';
        $senderCity = $this->societeConfig->getVille() ?? 'Saint-Sernin';
        $senderName = $this->societeConfig->getNom() ?? "Studio Pipelette";
        $senderEmail = $this->societeConfig->getEmail() ?? 'contact@code65.fr';

        $valueEurosStr = number_format($valueEuros, 2, '.', '');

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<ShipmentCreationRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://www.example.org/Request">
  <Context>
    <Login>{$this->login}</Login>
    <Password>{$this->password}</Password>
    <CustomerId>{$this->customerId}</CustomerId>
    <Culture>fr-FR</Culture>
    <VersionAPI>1.0</VersionAPI>
  </Context>
  <OutputOptions>
    <OutputFormat>10x15</OutputFormat>
    <OutputType>PdfUrl</OutputType>
  </OutputOptions>
  <ShipmentsList>
    <Shipment>
      <OrderNo>{$orderRef}</OrderNo>
      <CustomerNo>{$customerRef}</CustomerNo>
      <ParcelCount>{$parcelCount}</ParcelCount>
      <DeliveryMode Mode="{$deliveryMode}" Location="{$deliveryLocation}" />
      <CollectionMode Mode="{$collectionMode}" Location="" />
      <Parcels>
        <Parcel>
          <Content>{$this->xmlEscape($order->getMondialRelayContentDescription() ?? '')}</Content>
          <Weight Value="{$weightGrams}" Unit="gr" />
        </Parcel>
      </Parcels>
      <DeliveryInstruction />
      <Sender>
        <Address>
          <Title />
          <Firstname />
          <Lastname />
          <Streetname>{$this->xmlEscape($senderStreet)}</Streetname>
          <HouseNo>{$this->xmlEscape($senderHouseNo)}</HouseNo>
          <CountryCode>FR</CountryCode>
          <PostCode>{$this->xmlEscape($senderPostCode)}</PostCode>
          <City>{$this->xmlEscape($senderCity)}</City>
          <AddressAdd1>{$this->xmlEscape($senderName)}</AddressAdd1>
          <AddressAdd2 />
          <AddressAdd3 />
          <PhoneNo />
          <MobileNo />
          <Email>{$this->xmlEscape($senderEmail)}</Email>
        </Address>
      </Sender>
      <Recipient>
        <Address>
          <Title />
          <Firstname>{$this->xmlEscape($recipient['firstName'])}</Firstname>
          <Lastname>{$this->xmlEscape($recipient['lastName'])}</Lastname>
          <Streetname>{$this->xmlEscape($recipient['streetName'])}</Streetname>
          <HouseNo>{$this->xmlEscape($recipient['houseNo'])}</HouseNo>
          <CountryCode>{$this->xmlEscape($recipient['country'])}</CountryCode>
          <PostCode>{$this->xmlEscape($recipient['postalCode'])}</PostCode>
          <City>{$this->xmlEscape($recipient['city'])}</City>
          <AddressAdd1 />
          <AddressAdd2 />
          <AddressAdd3 />
          <PhoneNo>{$this->xmlEscape($recipient['phone'])}</PhoneNo>
          <MobileNo />
          <Email>{$this->xmlEscape($recipient['email'])}</Email>
        </Address>
      </Recipient>
    </Shipment>
  </ShipmentsList>
</ShipmentCreationRequest>
XML;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
