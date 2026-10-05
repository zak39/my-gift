<?php

namespace App\Form;

use App\Entity\Gift;
use App\Entity\Section;
use App\Repository\SectionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * A gift edited by its author: the creation fields of GiftType, minus the OpenGraph image
 * (only the creation page fetches one), plus the section the gift is filed in. Its author
 * files it in any list they run, their own or one of their crews', or in none, and so
 * moves it between them. Another member of its crew edits it with GiftCrewMemberEditType.
 */
class GiftEditType extends AbstractType
{
    public function __construct(
        private readonly SectionRepository $sectionRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Gift $gift */
        $gift = $builder->getData();

        $builder
            ->remove('openGraphImageUrl')
            ->add('section', EntityType::class, [
                'class' => Section::class,
                'choices' => $this->sectionRepository->findManagedBy($gift->getCreatedBy()),
                'choice_label' => 'title',
                // The sections of a crew's list apart from those of the user's own.
                'group_by' => fn (Section $section) => $section->getCrew()?->getName() ?? 'Ma liste',
                'help' => 'Sans section, il apparaît dans « Cadeaux sans section ».',
                'placeholder' => 'Dans aucune section',
                'required' => false,
            ])
        ;
    }

    public function getParent(): string
    {
        return GiftType::class;
    }
}
