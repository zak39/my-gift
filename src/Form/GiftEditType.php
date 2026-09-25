<?php

namespace App\Form;

use App\Entity\Section;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                'help' => 'Sans section, il apparaît dans « Cadeaux sans section ».',
                // Créer une nouvelle option pour la déplacer dans aucune section
                'placeholder' => 'Dans aucune section',
                'required' => false,
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
        ]);
    }
}
