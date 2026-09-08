<?php

namespace App\Form;

use App\Entity\Participant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantReserveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('anonymous', CheckboxType::class, [
                'label' => 'Voulez-vous offrir sans qu\'on sache qui sait ?',
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
