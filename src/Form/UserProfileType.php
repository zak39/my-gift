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
                'attr' => ['class' => 'input'],
                'label' => 'Prénom',
                'label_attr' => ['class' => 'label'],
            ])
            ->add('lastname', TextType::class, [
                'attr' => ['class' => 'input'],
                'label' => 'Nom',
                'label_attr' => ['class' => 'label'],
            ])
            ->add('oldPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
                'always_empty' => false,
                'attr' => [
                    'autocomplete' => 'current-password',
                    'class' => 'input',
                ],
                'label' => 'Mot de passe actuel',
                'label_attr' => ['class' => 'label'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => false,
                'mapped' => false,
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'input password-fields',
                    ],
                ],
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'label_attr' => ['class' => 'label'],
                    'always_empty' => false,
                ],
                'second_options' => [
                    'label' => 'Répétez le mot de passe',
                    'label_attr' => ['class' => 'label'],
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
