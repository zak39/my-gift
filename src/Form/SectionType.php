<?php

namespace App\Form;

use App\Entity\Section;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
                    'class' => 'input md:input-md input-xl w-full',
                ],
                'label' => 'Titre',
                'label_attr' => [
                    'class' => 'label md:text-base text-xl',
                ],
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'textarea md:textarea-md textarea-lg w-full',
                    'placeholder' => 'Description',
                ],
                'label' => 'Description',
                'label_attr' => [
                    'class' => 'label md:text-base text-xl',
                ],
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
        $resolver->setDefaults([
            'data_class' => Section::class,
        ]);
    }
}
