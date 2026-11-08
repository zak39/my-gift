<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                // RGAA 11.13: the field's purpose, for autofill and assistive technologies.
                'attr' => ['class' => 'input', 'autocomplete' => 'given-name'],
                'label' => 'Prénom',
                'label_attr' => ['class' => 'label'],
            ])
            ->add('lastname', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'input', 'autocomplete' => 'family-name'],
                // RGAA 11.10: an optional field says it in its label.
                'label' => 'Nom (facultatif)',
                'label_attr' => ['class' => 'label'],
            ])
            ->add('oldPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'always_empty' => false,
                'attr' => [
                    'autocomplete' => 'current-password',
                ],
                'label' => 'Mot de passe actuel',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => false,
                'mapped' => false,
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'always_empty' => false,
                ],
                'second_options' => [
                    'label' => 'Répétez le mot de passe',
                    'always_empty' => false,
                ],
            ])
            ->add('avatarFile', FileType::class, [
                'label' => 'Photo de profil',
                'label_attr' => ['class' => 'label'],
                'required' => false,
                'attr' => ['class' => 'file-input file-input-bordered w-full'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
