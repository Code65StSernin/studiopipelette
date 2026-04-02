<?php
namespace App\Controller\Admin;

use App\Entity\Tarif;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TarifCrudController extends AbstractCrudController
{
    public function __construct(private PictureService $pictureService)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Tarif::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tarif')
            ->setEntityLabelInPlural('Tarifs');
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            MoneyField::new('tarif')->setCurrency('EUR')->setStoredAsCents(false),
            IntegerField::new('dureeMinutes')->setLabel('Durée (minutes)'),
            AssociationField::new('categorieVente')->setLabel('Catégorie Vente'),
            AssociationField::new('sousCategorieVente')->setLabel('Sous-Catégorie Vente'),
            ColorField::new('couleurFond')->setLabel('Couleur fond')->setHelp('Couleur utilisée dans le planning (éviter l’orange).'),
            ColorField::new('couleurTexte')->setLabel('Couleur texte')->setHelp('Couleur du texte dans le planning (doit rester lisible).'),
        ];

        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            $fields[] = Field::new('image', 'Image')
                ->setFormType(FileType::class)
                ->setRequired(false)
                ->setFormTypeOptions([
                    'mapped' => false,
                    'required' => false,
                    'attr' => [
                        'accept' => 'image/jpeg,image/png,image/webp',
                    ],
                ])
                ->onlyOnForms();
        }

        return $fields;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $tarif = $entityInstance;
        if ($tarif instanceof Tarif) {
            $this->ensurePlanningColors($tarif);
        }

        $request = $this->getContext()->getRequest();
        $uploadedFile = null;

        $allFiles = $request->files->all();
        foreach ($allFiles as $formName => $formData) {
            if (is_array($formData) && isset($formData['image']) && $formData['image'] instanceof UploadedFile) {
                $uploadedFile = $formData['image'];
                break;
            }
        }

        if ($uploadedFile instanceof UploadedFile) {
            $filename = $this->pictureService->add($uploadedFile, '/tarifs', 200, 200);
            $tarif->setImage($filename);
        }

        parent::persistEntity($entityManager, $tarif);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $tarif = $entityInstance;
        if ($tarif instanceof Tarif) {
            $this->ensurePlanningColors($tarif);
        }

        $request = $this->getContext()->getRequest();
        $uploadedFile = null;

        $allFiles = $request->files->all();
        foreach ($allFiles as $formName => $formData) {
            if (is_array($formData) && isset($formData['image']) && $formData['image'] instanceof UploadedFile) {
                $uploadedFile = $formData['image'];
                break;
            }
        }

        if ($uploadedFile instanceof UploadedFile) {
            if ($tarif->getImage()) {
                $this->pictureService->delete($tarif->getImage(), '/tarifs', 300, 300);
            }

            $filename = $this->pictureService->add($uploadedFile, '/tarifs', 300, 300);
            $tarif->setImage($filename);
        }

        parent::updateEntity($entityManager, $tarif);
    }

    private function ensurePlanningColors(Tarif $tarif): void
    {
        $bg = $tarif->getCouleurFond();
        if (!$bg) {
            $palette = $this->palette();
            $bg = $palette[random_int(0, count($palette) - 1)];
            $tarif->setCouleurFond($bg);
        }

        $text = $tarif->getCouleurTexte();
        if (!$text) {
            $tarif->setCouleurTexte($this->textColorForBackground($bg));
        }
    }

    private function palette(): array
    {
        return [
            '#1E88E5',
            '#3949AB',
            '#5E35B1',
            '#8E24AA',
            '#C2185B',
            '#00897B',
            '#00ACC1',
            '#43A047',
            '#7CB342',
            '#9E9D24',
            '#6D4C41',
            '#546E7A',
            '#E53935',
        ];
    }

    private function textColorForBackground(string $hex): string
    {
        $hex = strtoupper(trim($hex));
        if (str_starts_with($hex, '#')) {
            $hex = substr($hex, 1);
        }
        if (strlen($hex) !== 6) {
            return '#FFFFFF';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $l = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $l > 0.6 ? '#000000' : '#FFFFFF';
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $tarif = $entityInstance;

        if ($tarif->getImage()) {
            $this->pictureService->delete($tarif->getImage(), '/tarifs', 200, 200);
        }

        parent::deleteEntity($entityManager, $tarif);
    }
}
