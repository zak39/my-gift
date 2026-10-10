<?php

namespace App\Form;

use App\Entity\Section;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'placeholder' => 'Geek, Sport, Maison…',
                ],
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Tout ce qui touche aux jeux vidéo et aux gadgets.',
                ],
                'label' => 'Description',
                'help' => 'Une phrase pour guider vos amis. 255 caractères maximum.',
                'required' => false,
            ])
            /*
            ->add('slug')
            ->add('createdBy', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // No list field: the list comes from the URL (/me/sections or /crews/{id}/sections),
        // and a section never moves from one list to another.
        $resolver->setDefaults([
            'data_class' => Section::class,
        ]);
    }
}
