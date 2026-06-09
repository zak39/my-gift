<?php

namespace App\Form;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GiftType extends AbstractType
{
    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'input',
                ],
                'label' => 'Titre du cadeau',
                'label_attr' => [
                    'class' => 'label',
                ],
            ])
            ->add('description', TextType::class, [
                'attr' => [
                    'class' => 'input',
                ],
                'label' => 'Description du cadeau',
                'label_attr' => [
                    'class' => 'label',
                ],
            ])
            ->add('link', UrlType::class, [
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'URL',
                ],
                'label' => 'Lien',
                'label_attr' => [
                    'class' => 'label',
                ],
                'required' => false,
            ])
            ->add('image', FileType::class, [
                'attr' => [
                    'class' => 'file-input',
                ],
                'label_attr' => [
                    'class' => 'label',
                ],
                'required' => false,
            ])
            ->add('price', MoneyType::class, [
                'attr' => [
                    'class' => 'input',
                ],
                'label' => 'Prix du cadeau',
                'label_attr' => [
                    'class' => 'label',
                ],
            ])
            ->add('isPublic', CheckboxType::class, [
                'attr' => [
                    'class' => 'toggle toggle-primary',
                ],
                'label' => 'Afficher publiquement le cadeau',
                'label_attr' => [
                    'class' => 'label',
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
