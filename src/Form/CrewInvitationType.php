<?php

namespace App\Form;

use App\Entity\Crew;
use App\Entity\User;
use App\Repository\FriendBookRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Not mapped to a CrewInvitation: CrewLifecycle::invite() builds it. Offers the inviter's
 * friends who are neither members of the crew nor invited yet, and rejects any other id.
 * The crew's page shows it and Crew\InvitationController handles it: both build it from
 * the same options, so they offer the same people.
 */
class CrewInvitationType extends AbstractType
{
    public function __construct(
        private readonly FriendBookRepository $friendBookRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Crew $crew */
        $crew = $options['crew'];

        $candidates = $this->friendBookRepository->of($options['inviter'])->getFriends()->filter(
            fn (User $friend) => !$crew->hasMember($friend) && null === $crew->getInvitationFor($friend),
        );

        $builder
            ->add('invitee', EntityType::class, [
                'class' => User::class,
                'choices' => $candidates->getValues(),
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
        $resolver->setRequired(['crew', 'inviter']);
        $resolver->setAllowedTypes('crew', Crew::class);
        $resolver->setAllowedTypes('inviter', User::class);
    }
}
