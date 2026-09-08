<?php

namespace App\Form;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantContributingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'label md:text-base text-xl',
                ],
            ])
            ->add('anonymous', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'checkbox checkbox-neutral',
                ],
            ])
            ->add('pseudoAnonyme', TextType::class, [
                'label' => 'Pseudo',
                'required' => false,
                'attr' => [
                    'class' => 'input',
                ],
            ])
            /*
            ->add('gift', EntityType::class, [
                'class' => Gift::class,
                'choice_label' => 'id',
            ])
            ->add('participantUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
