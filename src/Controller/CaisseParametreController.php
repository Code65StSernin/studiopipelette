<?php

namespace App\Controller;

use App\Entity\Societe;
use App\Repository\SocieteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;

#[Route('/caisse/parametres')]
class CaisseParametreController extends AbstractController
{
    #[Route('/generaux', name: 'app_caisse_parametres_generaux')]
    public function generaux(
        Request $request,
        SocieteRepository $societeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $societe = $societeRepository->findOneBy([]);
        
        if (!$societe) {
            $societe = new Societe();
            $societe->setNom('Ma Société'); // Default name to avoid constraints issues if any
            $entityManager->persist($societe);
        }

        // Capture PIN BEFORE creating form (which binds request data)
        $originalPin = $societe->getAdminPin();
        $effectiveOriginalPin = ($originalPin !== null && trim($originalPin) !== '') ? trim($originalPin) : '1234';

        $form = $this->createFormBuilder($societe)
            ->add('currentPin', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Code PIN actuel',
                'attr' => [
                    'class' => 'form-control-lg text-center',
                    'maxlength' => 10,
                    'autocomplete' => 'off'
                ],
                'help' => 'Requis pour modifier le code PIN ci-dessous.'
            ])
            ->add('adminPin', TextType::class, [
                'label' => 'Nouveau Code PIN',
                'required' => false,
                'attr' => [
                    'placeholder' => '1234 (par défaut)',
                    'class' => 'form-control-lg text-center',
                    'maxlength' => 10
                ],
                'help' => 'Ce code est utilisé pour les opérations sensibles (ex: vente forcée). Laisser vide pour utiliser le code par défaut (1234).'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary w-100']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPin = $societe->getAdminPin();
            
            // Normalize for comparison
            $normalizedNewPin = ($newPin !== null && trim($newPin) !== '') ? trim($newPin) : null;
            $normalizedOriginalPin = ($originalPin !== null && trim($originalPin) !== '') ? trim($originalPin) : null;

            if ($normalizedNewPin !== $normalizedOriginalPin) {
                $enteredCurrentPin = $form->get('currentPin')->getData();
                $enteredCurrentPin = is_string($enteredCurrentPin) ? trim($enteredCurrentPin) : '';

                if (!hash_equals($effectiveOriginalPin, $enteredCurrentPin)) {
                    $form->get('currentPin')->addError(new FormError('Le code PIN actuel est incorrect.'));
                }
            }

            if ($form->isValid()) {
                $entityManager->flush();
                $this->addFlash('success', 'Paramètres enregistrés avec succès.');
                return $this->redirectToRoute('app_caisse_parametres_generaux');
            }
        }

        return $this->render('caisse/parametres/generaux.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
