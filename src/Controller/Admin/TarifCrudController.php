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

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $tarif = $entityInstance;

        if ($tarif->getImage()) {
            $this->pictureService->delete($tarif->getImage(), '/tarifs', 200, 200);
        }

        parent::deleteEntity($entityManager, $tarif);
    }
}
