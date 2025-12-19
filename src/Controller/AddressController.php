<?php

namespace App\Controller;

use App\Entity\Address;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AddressController extends AbstractController
{
    #[Route('/api/addresses', name: 'app_get_addresses', methods: ['GET'])]
    public function getAddresses(AddressRepository $addressRepository): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté.'], 401);
        }

        $addresses = $addressRepository->findBy(['user' => $user], ['isDefault' => 'DESC', 'id' => 'ASC']);
        
        $data = array_map(function(Address $address) {
            return [
                'id' => $address->getId(),
                'streetNumber' => $address->getStreetNumber(),
                'street' => $address->getStreet(),
                'complement' => $address->getComplement(),
                'postalCode' => $address->getPostalCode(),
                'city' => $address->getCity(),
                'isDefault' => $address->isDefault(),
                'fullAddress' => $address->getFullAddress(),
            ];
        }, $addresses);

        return new JsonResponse($data);
    }

    #[Route('/api/addresses', name: 'app_create_address', methods: ['POST'])]
    public function createAddress(Request $request, EntityManagerInterface $entityManager, AddressRepository $addressRepository): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté.'], 401);
        }

        // Vérifier le nombre maximum d'adresses (5)
        $existingAddresses = $addressRepository->findBy(['user' => $user]);
        if (count($existingAddresses) >= 5) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas avoir plus de 5 adresses.'], 400);
        }

        $data = $request->request->all();
        
        // Validation des longueurs
        if (strlen($data['street_number'] ?? '') > 5) {
            return new JsonResponse(['error' => 'Le numéro ne peut pas dépasser 5 caractères.'], 400);
        }
        if (strlen($data['street'] ?? '') > 90) {
            return new JsonResponse(['error' => 'La voie ne peut pas dépasser 90 caractères.'], 400);
        }
        if (isset($data['complement']) && strlen($data['complement']) > 90) {
            return new JsonResponse(['error' => 'Le complément ne peut pas dépasser 90 caractères.'], 400);
        }
        if (strlen($data['postal_code'] ?? '') != 5 || !preg_match('/^[0-9]{5}$/', $data['postal_code'] ?? '')) {
            return new JsonResponse(['error' => 'Le code postal doit contenir exactement 5 chiffres.'], 400);
        }
        if (strlen($data['city'] ?? '') > 30) {
            return new JsonResponse(['error' => 'La ville ne peut pas dépasser 30 caractères.'], 400);
        }

        
        $address = new Address();
        $address->setStreetNumber($data['street_number'] ?? '');
        $address->setStreet($data['street'] ?? '');
        $address->setComplement($data['complement'] ?? null);
        $address->setPostalCode($data['postal_code'] ?? '');
        $address->setCity($data['city'] ?? '');
        $address->setUser($user);

        // Si c'est la première adresse, elle devient par défaut
        $isDefault = count($existingAddresses) === 0;
        $address->setIsDefault($isDefault);

        $entityManager->persist($address);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Adresse ajoutée avec succès.',
            'address' => [
                'id' => $address->getId(),
                'streetNumber' => $address->getStreetNumber(),
                'street' => $address->getStreet(),
                'complement' => $address->getComplement(),
                'postalCode' => $address->getPostalCode(),
                'city' => $address->getCity(),
                'isDefault' => $address->isDefault(),
                'fullAddress' => $address->getFullAddress(),
            ]
        ]);
    }

    #[Route('/api/addresses/{id}', name: 'app_update_address', methods: ['PUT', 'POST'])]
    public function updateAddress(int $id, Request $request, AddressRepository $addressRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté.'], 401);
        }

        $address = $addressRepository->find($id);
        
        if (!$address || $address->getUser() !== $user) {
            return new JsonResponse(['error' => 'Adresse introuvable.'], 404);
        }

        $data = $request->request->all();
        
        // Validation des longueurs
        if (isset($data['street_number']) && strlen($data['street_number']) > 5) {
            return new JsonResponse(['error' => 'Le numéro ne peut pas dépasser 5 caractères.'], 400);
        }
        if (isset($data['street']) && strlen($data['street']) > 90) {
            return new JsonResponse(['error' => 'La voie ne peut pas dépasser 90 caractères.'], 400);
        }
        if (isset($data['complement']) && strlen($data['complement']) > 90) {
            return new JsonResponse(['error' => 'Le complément ne peut pas dépasser 90 caractères.'], 400);
        }
        if (isset($data['postal_code']) && (strlen($data['postal_code']) != 5 || !preg_match('/^[0-9]{5}$/', $data['postal_code']))) {
            return new JsonResponse(['error' => 'Le code postal doit contenir exactement 5 chiffres.'], 400);
        }
        if (isset($data['city']) && strlen($data['city']) > 30) {
            return new JsonResponse(['error' => 'La ville ne peut pas dépasser 30 caractères.'], 400);
        }
        
        $address->setStreetNumber($data['street_number'] ?? $address->getStreetNumber());
        $address->setStreet($data['street'] ?? $address->getStreet());
        $address->setComplement($data['complement'] ?? null);
        $address->setPostalCode($data['postal_code'] ?? $address->getPostalCode());
        $address->setCity($data['city'] ?? $address->getCity());

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Adresse modifiée avec succès.',
            'address' => [
                'id' => $address->getId(),
                'streetNumber' => $address->getStreetNumber(),
                'street' => $address->getStreet(),
                'complement' => $address->getComplement(),
                'postalCode' => $address->getPostalCode(),
                'city' => $address->getCity(),
                'isDefault' => $address->isDefault(),
                'fullAddress' => $address->getFullAddress(),
            ]
        ]);
    }

    #[Route('/api/addresses/{id}', name: 'app_delete_address', methods: ['DELETE'])]
    public function deleteAddress(int $id, AddressRepository $addressRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté.'], 401);
        }

        $address = $addressRepository->find($id);
        
        if (!$address || $address->getUser() !== $user) {
            return new JsonResponse(['error' => 'Adresse introuvable.'], 404);
        }

        $wasDefault = $address->isDefault();
        
        $entityManager->remove($address);
        $entityManager->flush();

        // Si c'était l'adresse par défaut, définir la suivante comme par défaut
        if ($wasDefault) {
            $remainingAddresses = $addressRepository->findBy(['user' => $user], ['id' => 'ASC'], 1);
            if (count($remainingAddresses) > 0) {
                $remainingAddresses[0]->setIsDefault(true);
                $entityManager->flush();
            }
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Adresse supprimée avec succès.'
        ]);
    }

    #[Route('/api/addresses/{id}/set-default', name: 'app_set_default_address', methods: ['POST'])]
    public function setDefaultAddress(int $id, AddressRepository $addressRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté.'], 401);
        }

        $address = $addressRepository->find($id);
        
        if (!$address || $address->getUser() !== $user) {
            return new JsonResponse(['error' => 'Adresse introuvable.'], 404);
        }

        // Retirer le statut par défaut de toutes les autres adresses
        $allAddresses = $addressRepository->findBy(['user' => $user]);
        foreach ($allAddresses as $addr) {
            $addr->setIsDefault(false);
        }

        // Définir cette adresse comme par défaut
        $address->setIsDefault(true);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Adresse définie par défaut.'
        ]);
    }
}

