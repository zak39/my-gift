<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The pseudo field, the same at registration and in the profile: its rules come from User,
 * which validates it.
 */
final class PseudoType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Pseudo',
            // RGAA 11.10: the expected format, tied to the field (aria-describedby).
            'help' => \sprintf(
                // "-" last: a line may break after a hyphen, even before a non-breaking space.
                'De %d à %d caractères : lettres sans accent, chiffres, espaces, #, _ et -',
                User::PSEUDO_MIN_LENGTH,
                User::PSEUDO_MAX_LENGTH,
            ),
            // RGAA 11.13: the field's purpose.
            'attr' => ['autocomplete' => 'nickname'],
            'label_attr' => ['class' => 'label'],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
