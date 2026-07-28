<?php

namespace App\Form;

use App\Entity\Gift;
use App\Entity\Section;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GiftEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du cadeau',
                'label_attr' => [
                    'class' => 'label text-base',
                ],
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'textarea textarea-md w-full',
                ],
                'label' => 'Description du cadeau',
                'label_attr' => [
                    'class' => 'label text-base',
                ],
                'required' => false,
            ])
            ->add('link', UrlType::class, [
                'attr' => [
                    'placeholder' => 'URL',
                ],
                'label' => 'Lien',
                'label_attr' => [
                    'class' => 'label text-base',
                ],
                'required' => false,
            ])
            ->add('imageFile', FileType::class, [
                'attr' => [
                    'class' => 'file-input input-md w-full',
                ],
                'label' => 'Image',
                'label_attr' => [
                    'class' => 'label text-base',
                ],
                'required' => false,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix du cadeau',
                'label_attr' => [
                    'class' => 'label text-base',
                ],
            ])
            ->add('isPublic', CheckboxType::class, [
                'attr' => [
                    'class' => 'toggle toggle-primary mb-4 md:toggle-md toggle-lg',
                ],
                'label' => 'Afficher publiquement le cadeau',
                'label_attr' => [
                    'class' => 'label text-base mb-2',
                ],
                'required' => false,
            ])
            ->add('section', EntityType::class, [
                'class' => Section::class,
                'choices' => $options['sections'],
                'attr' => [
                    'class' => 'select select-md w-full',
                ],
                'choice_label' => 'title',
                'label_attr' => [
                    'class' => 'label text-base mb-2',
                ],
                // Créer une nouvelle option pour la déplacer dans aucune section
                'placeholder' => "Dans aucune section",
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gift::class,
            'sections' => [],
        ]);
    }
}
