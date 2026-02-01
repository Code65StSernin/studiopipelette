<?php

namespace App\Controller;

use App\Entity\Societe;
use App\Entity\EtiquetteFormat;
use App\Repository\SocieteRepository;
use App\Repository\EtiquetteFormatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
            // Programme de Fidélité
            ->add('fideliteActive', CheckboxType::class, [
                'label' => 'Activer le programme de fidélité',
                'required' => false,
            ])
            ->add('fideliteMode', ChoiceType::class, [
                'label' => 'Mode de fonctionnement',
                'choices' => [
                    'Toutes les X ventes, ajouter Y €' => 'visits',
                    'Cumuler X points/€, à Y points gagner Z €' => 'points',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            // Mode "Visits"
            ->add('fideliteVisitsX', IntegerType::class, [
                'label' => 'Nombre de ventes (X)',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 10'],
            ])
            ->add('fideliteVisitsY', NumberType::class, [
                'label' => 'Montant offert (Y)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'ex: 15.00'],
            ])
            // Mode "Points"
            ->add('fidelitePointsX', NumberType::class, [
                'label' => 'Points par Euro (X)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'ex: 1'],
            ])
            ->add('fidelitePointsY', NumberType::class, [
                'label' => 'Seuil de points (Y)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'ex: 500'],
            ])
            ->add('fidelitePointsZ', NumberType::class, [
                'label' => 'Montant offert (Z)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'ex: 20.00'],
            ])
            
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

    #[Route('/etiquettes', name: 'app_caisse_parametres_etiquettes')]
    public function etiquettes(
        Request $request,
        EtiquetteFormatRepository $etiquetteFormatRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $etiquetteFormat = $etiquetteFormatRepository->findOneBy([]);
        
        if (!$etiquetteFormat) {
            $etiquetteFormat = new EtiquetteFormat();
            // Default values
            $etiquetteFormat->setMargeHaut(0.0);
            $etiquetteFormat->setMargeBas(0.0);
            $etiquetteFormat->setMargeGauche(0.0);
            $etiquetteFormat->setMargeDroite(0.0);
            $etiquetteFormat->setDistanceHorizontale(0.0);
            $etiquetteFormat->setDistanceVerticale(0.0);
            $etiquetteFormat->setLargeur(0.0);
            $etiquetteFormat->setHauteur(0.0);
            $entityManager->persist($etiquetteFormat);
        }

        $form = $this->createFormBuilder($etiquetteFormat)
            ->add('margeHaut', NumberType::class, [
                'label' => 'Marge Haute (cm)',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'class' => 'etiquette-input'],
            ])
            ->add('margeBas', NumberType::class, [
                'label' => 'Marge Basse (cm)',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'class' => 'etiquette-input'],
            ])
            ->add('margeGauche', NumberType::class, [
                'label' => 'Marge Gauche (cm)',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'class' => 'etiquette-input'],
            ])
            ->add('margeDroite', NumberType::class, [
                'label' => 'Marge Droite (cm)',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'class' => 'etiquette-input'],
            ])
            ->add('distanceHorizontale', NumberType::class, [
                'label' => 'Ecart Horizontal (cm)',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'class' => 'etiquette-input'],
            ])
            ->add('distanceVerticale', NumberType::class, [
                'label' => 'Ecart Vertical (cm)',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'class' => 'etiquette-input'],
            ])
            ->add('largeur', NumberType::class, [
                'label' => 'Largeur Etiquette (cm)',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'class' => 'etiquette-input'],
            ])
            ->add('hauteur', NumberType::class, [
                'label' => 'Hauteur Etiquette (cm)',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'class' => 'etiquette-input'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary w-100']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Format d\'étiquettes enregistré avec succès.');
            return $this->redirectToRoute('app_caisse_parametres_etiquettes');
        }

        return $this->render('caisse/parametres/etiquettes.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
