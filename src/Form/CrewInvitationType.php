<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Not mapped to a CrewInvitation: CrewLifecycle::invite() builds it. The controller works
 * out the candidates, the inviter's friends who are neither members nor invited yet, and
 * the form rejects any other id.
 */
class CrewInvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('invitee', EntityType::class, [
                'class' => User::class,
                'choices' => $options['candidates'],
                'choice_label' => 'fullname',
                'label' => 'Inviter un·e ami·e',
                'placeholder' => 'Choisissez parmi vos ami·es',
                'constraints' => [
                    new Assert\NotNull(message: 'Choisissez la personne à inviter.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'candidates' => [],
        ]);
        $resolver->setAllowedTypes('candidates', User::class.'[]');
    }
}
