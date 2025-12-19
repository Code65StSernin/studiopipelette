<?php

namespace App\Controller;

use App\Entity\Faq;
use App\Repository\FaqRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FaqController extends AbstractController
{
    #[Route('/faq', name: 'app_faq')]
    public function index(FaqRepository $faqRepository, EntityManagerInterface $em): Response
    {
        // Si aucune FAQ n'existe encore, on crée une vingtaine de questions/réponses par défaut
        if ($faqRepository->count([]) === 0) {
            $seed = [
                [
                    'q' => 'Comment créer un compte sur le site ?',
                    'a' => '<p>Cliquez sur le bouton <strong>Inscription</strong> en haut de la page, puis remplissez le formulaire avec vos informations (nom, prénom, email, mot de passe).</p><p>Vous recevrez ensuite un email pour valider votre compte.</p>',
                ],
                [
                    'q' => 'Je n’ai pas reçu l’email de confirmation, que faire ?',
                    'a' => '<p>Commencez par vérifier vos dossiers <strong>courriers indésirables</strong> ou <strong>spam</strong>.</p><p>Si vous ne trouvez toujours rien après quelques minutes, demandez un nouvel envoi depuis la page de connexion ou contactez-nous via le formulaire de contact.</p>',
                ],
                [
                    'q' => 'Puis-je passer une commande sans créer de compte ?',
                    'a' => '<p>Actuellement, la création d’un compte est nécessaire pour passer commande.</p><p>Cela vous permet de suivre vos commandes, de retrouver vos factures et de gérer vos adresses de livraison plus facilement.</p>',
                ],
                [
                    'q' => 'Comment ajouter un article à mon panier ?',
                    'a' => '<p>Depuis la fiche produit, choisissez la taille souhaitée puis cliquez sur le bouton <strong>Ajouter au panier</strong>.</p><p>Votre panier reste accessible en permanence via l’icône panier en haut du site.</p>',
                ],
                [
                    'q' => 'Comment modifier la quantité d’un article dans mon panier ?',
                    'a' => '<p>Dans la page <strong>Panier</strong>, utilisez les boutons <strong>+</strong> et <strong>−</strong> à côté de chaque article pour ajuster la quantité.</p><p>Le total est recalculé automatiquement.</p>',
                ],
                [
                    'q' => 'Comment supprimer un article de mon panier ?',
                    'a' => '<p>Dans votre panier, cliquez sur l’icône <strong>poubelle</strong> située à droite de la ligne de l’article concerné.</p><p>Vous pouvez aussi vider complètement le panier avec le bouton <strong>Vider le panier</strong>.</p>',
                ],
                [
                    'q' => 'Quels sont les modes de livraison proposés ?',
                    'a' => '<p>Nous proposons principalement la livraison en <strong>point relais Mondial Relay</strong>.</p><p>Selon votre adresse, une <strong>livraison à domicile</strong> peut également être disponible lors du choix du mode de livraison.</p>',
                ],
                [
                    'q' => 'Quels sont les délais de livraison ?',
                    'a' => '<p>Les commandes sont en général préparées sous <strong>48 h ouvrées</strong>, puis remises au transporteur.</p><p>Les délais de transport varient ensuite entre <strong>3 et 5 jours ouvrés</strong> en moyenne.</p>',
                ],
                [
                    'q' => 'Comment suivre l’état de ma commande ?',
                    'a' => '<p>Depuis votre compte, rubrique <strong>Commandes</strong>, vous pouvez suivre le statut de chaque commande.</p><p>Lors de l’expédition, un email avec le numéro de suivi Mondial Relay vous est envoyé.</p>',
                ],
                [
                    'q' => 'Comment accéder à mes factures ?',
                    'a' => '<p>Toutes vos factures sont disponibles dans la rubrique <strong>Factures</strong> de votre compte.</p><p>Vous pouvez les consulter à l’écran ou les télécharger au format PDF.</p>',
                ],
                [
                    'q' => 'Quels moyens de paiement sont acceptés ?',
                    'a' => '<p>Le paiement est sécurisé et géré par <strong>Stripe</strong>, qui accepte la plupart des cartes bancaires (Visa, Mastercard, etc.).</p>',
                ],
                [
                    'q' => 'Mon paiement a été refusé, que faire ?',
                    'a' => '<p>Vérifiez les informations de votre carte, votre plafond et la validation éventuelle du 3D Secure.</p><p>Si le problème persiste, contactez votre banque ou essayez avec une autre carte.</p>',
                ],
                [
                    'q' => 'Comment utiliser un code promo ?',
                    'a' => '<p>Si vous disposez d’un code promo, saisissez-le dans le champ prévu lors de la validation de votre panier.</p><p>La remise s’appliquera automatiquement si le code est valide.</p>',
                ],
                [
                    'q' => 'Puis-je modifier ou annuler une commande après validation ?',
                    'a' => '<p>Si votre commande n’a pas encore été préparée, il est parfois possible de la modifier ou de l’annuler.</p><p>Contactez-nous au plus vite en indiquant votre numéro de commande.</p>',
                ],
                [
                    'q' => 'Comment mettre à jour mon adresse de livraison ?',
                    'a' => '<p>Dans <strong>Mon compte &gt; Adresses</strong>, vous pouvez ajouter, modifier ou supprimer vos adresses.</p><p>Vérifiez toujours l’adresse sélectionnée avant de valider une commande.</p>',
                ],
                [
                    'q' => 'Comment ajouter un article à mes favoris ?',
                    'a' => '<p>Sur chaque fiche produit, cliquez sur l’icône de <strong>cœur</strong> pour ajouter l’article à vos favoris.</p><p>Vous retrouverez ensuite la liste complète dans la rubrique Favoris de votre compte.</p>',
                ],
                [
                    'q' => 'Puis-je suivre ma commande sans être connecté ?',
                    'a' => '<p>Le suivi détaillé (factures, historique, statut précis) nécessite d’être connecté à votre compte.</p><p>En revanche, vous pouvez utiliser le numéro de suivi Mondial Relay reçu par email sur le site du transporteur.</p>',
                ],
                [
                    'q' => 'Comment sont emballées les bougies ?',
                    'a' => '<p>Nous apportons un soin particulier à l’emballage pour protéger les bougies pendant le transport.</p><p>Les colis sont calés et protégés afin de limiter au maximum les chocs.</p>',
                ],
                [
                    'q' => 'Que faire si mon colis arrive abîmé ?',
                    'a' => '<p>Si le colis est visiblement endommagé, refusez-le ou émettez une réserve lors de la remise.</p><p>Contactez-nous ensuite avec des photos du colis et du contenu afin que nous trouvions rapidement une solution.</p>',
                ],
                [
                    'q' => 'Comment contacter le support en cas de problème ?',
                    'a' => '<p>Vous pouvez nous écrire via le <strong>formulaire de contact</strong> disponible dans le menu et en bas de page.</p><p>Nous vous répondrons dans les meilleurs délais, généralement sous 24 à 48h ouvrées.</p>',
                ],
            ];

            foreach ($seed as $row) {
                $faq = new Faq();
                $faq->setQuestion($row['q']);
                $faq->setAnswer($row['a']);
                $em->persist($faq);
            }

            $em->flush();
        }

        $items = $faqRepository->findAll();

        return $this->render('legal/faq.html.twig', [
            'items' => $items,
        ]);
    }
}
