<?php

namespace App\Form;

use App\Entity\Gift;
use App\Entity\Section;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A crew's gift edited by a member who is not its author: the fields of GiftType, minus
 * the OpenGraph image, plus a section among the crew's only. Out of the crew's list, the
 * gift would land in its author's, which is theirs to decide (GiftEditType).
 */
class GiftCrewMemberEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Gift $gift */
        $gift = $builder->getData();

        $builder
            ->remove('openGraphImageUrl')
            ->add('section', EntityType::class, [
                'class' => Section::class,
                'choices' => $gift->getCrew()->getSections()->getValues(),
                'choice_label' => 'title',
                'help' => 'Le cadeau appartient au groupe : il ne peut changer que pour une autre de ses sections.',
                // Required is not enough: an empty value can still be posted.
                'constraints' => [new Assert\NotNull(message: 'Choisissez une section du groupe.')],
            ])
        ;
    }

    public function getParent(): string
    {
        return GiftType::class;
    }
}
