<?php

namespace App\Form;

use App\Entity\Gift;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GiftType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'input md:input-md input-xl w-full',
                ],
                'label' => 'Titre du cadeau',
                'label_attr' => [
                    'class' => 'label md:text-base text-xl',
                ],
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'textarea md:textarea-md textarea-lg w-full',
                ],
                'label' => 'Description du cadeau',
                'label_attr' => [
                    'class' => 'label md:text-base text-xl',
                ],
            ])
            ->add('link', UrlType::class, [
                'attr' => [
                    'class' => 'input md:input-md input-xl w-full',
                    'placeholder' => 'URL',
                ],
                'label' => 'Lien',
                'label_attr' => [
                    'class' => 'label md:text-base text-xl',
                ],
                'required' => false,
            ])
            ->add('image', FileType::class, [
                'attr' => [
                    'class' => 'file-input md:input-md input-xl w-full',
                ],
                'label_attr' => [
                    'class' => 'label md:text-base text-xl',
                ],
                'required' => false,
            ])
            ->add('price', MoneyType::class, [
                'attr' => [
                    'class' => 'input md:input-md input-xl w-full',
                ],
                'label' => 'Prix du cadeau',
                'label_attr' => [
                    'class' => 'label md:text-base text-xl',
                ],
            ])
            ->add('isPublic', CheckboxType::class, [
                'attr' => [
                    'class' => 'toggle toggle-primary mb-8 md:toggle-md toggle-lg',
                ],
                'label' => 'Afficher publiquement le cadeau',
                'label_attr' => [
                    'class' => 'label md:text-base text-xl mb-2',
                ],
                'required' => false,
            ])
            /*
            ->add('slug', TextType::class, [
                'attr' => [
                    'class' => 'input'
                ],
                'label' => 'Prix du cadeau',
                'label_attr' => [
                    'class' => 'label'
                ]
            ])
            */
            /*
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            */
            /*
            ->add('status', EnumType::class)
            */
            /*
            ->add('createdBy', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            */
            /*
            ->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event): void {
                $gift = $event->getData();
                $form = $event->getForm();

                if (!$gift)
                {
                    return;
                }

                $user['createdAt'] = new \DateTimeImmutable();
                $user['createdBy'] = $this->security->getUser();

                $event->setData($user);
            })
            ->getForm()
            */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gift::class,
        ]);
    }
}
