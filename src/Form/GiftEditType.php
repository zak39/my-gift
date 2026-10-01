<?php

namespace App\Form;

use App\Entity\Section;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The creation fields of GiftType, minus the OpenGraph image (only the creation page
 * fetches one), plus the section the gift is filed in.
 */
class GiftEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->remove('openGraphImageUrl')
            ->add('section', EntityType::class, [
                'class' => Section::class,
                'choices' => $options['sections'],
                'choice_label' => 'title',
                // The sections of a crew's list apart from those of the user's own.
                'group_by' => fn (Section $section) => $section->getCrew()?->getName() ?? 'Ma liste',
                'help' => $options['allow_unfiled']
                    ? 'Sans section, il apparaît dans « Cadeaux sans section ».'
                    : 'Le cadeau appartient à la liste du groupe : il ne peut changer que de section.',
                // For its author, an option to take the gift out of any section.
                'placeholder' => $options['allow_unfiled'] ? 'Dans aucune section' : false,
                'required' => !$options['allow_unfiled'],
                // Hiding the option is not enough: an empty value can still be posted.
                'constraints' => $options['allow_unfiled'] ? [] : [new Assert\NotNull(message: 'Choisissez une section du groupe.')],
            ])
        ;
    }

    public function getParent(): string
    {
        return GiftType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'sections' => [],
            // Out of any section, a gift is its author's: no one else may take it there.
            'allow_unfiled' => true,
        ]);
        $resolver->setAllowedTypes('allow_unfiled', 'bool');
    }
}
