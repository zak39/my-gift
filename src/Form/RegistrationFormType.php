<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', options: [
                'attr' => [
                    'class' => 'input',
                ],
                'label' => 'Email',
                'label_attr' => [
                    'class' => 'label',
                ],
            ])
            ->add('lastname', TextType::class, [
                'attr' => [
                    'class' => 'input',
                ],
                'label' => 'Nom',
                'label_attr' => [
                    'class' => 'label',
                ],
            ])
            ->add('firstname', TextType::class, [
                'attr' => [
                    'class' => 'input',
                ],
                'label' => 'Prénom',
                'label_attr' => [
                    'class' => 'label',
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'You should agree to our terms.',
                    ),
                ],
                'attr' => [
                    'class' => 'checkbox checkbox-primary my-4',
                ],
                'label' => 'Agree terms',
                'label_attr' => [
                    'class' => 'label mr-4',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les champs de mot de passes doivent correspondre.',
                'required' => true,
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'input password-fields',
                    ],
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Please enter a password',
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        max: 4096,
                    ),
                ],
                'first_options' => [
                    'label' => 'Mot de passe',
                    'label_attr' => [
                        'class' => 'label',
                    ],
                ],
                'second_options' => [
                    'label' => 'Répétez le mot de passe',
                    'label_attr' => [
                        'class' => 'label',
                    ],
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
