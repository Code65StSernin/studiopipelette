<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Photo;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('article', EntityType::class, [
                'class' => Article::class,
                'choice_label' => 'nom',
                'label' => 'Article',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Image' => 'image',
                    'Vidéo' => 'video'
                ],
                'label' => 'Type',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('file', FileType::class, [
                'label' => 'Fichier (Image ou Vidéo)',
                'mapped' => false,
                'required' => $options['is_new'], // Required only on creation
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/ogg',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                            'video/mp4',
                            'video/webm',
                            'video/ogg',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image (JPG, PNG, GIF, WEBP) ou une vidéo (MP4, WEBM, OGG) de maximum 5Mo.',
                    ])
                ],
                'help' => 'Images: JPG, PNG, GIF, WEBP. Vidéos: MP4, WEBM, OGG (max 5Mo).'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Photo::class,
            'is_new' => false,
        ]);
        
        $resolver->setAllowedTypes('is_new', 'bool');
    }
}
