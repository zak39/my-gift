<?php

namespace App\Form;

use App\Entity\FriendBook;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * The end of the user's invitation link, chosen by them: « Les Caribous et les Petits »
 * becomes les-caribous-et-les-petits. Its rules come from FriendBook, which validates it.
 */
final class InvitationSlugType extends AbstractType
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('invitationSlug', TextType::class, [
            // Short, on one line with its « Requis »: the link's beginning sits inside the field
            // (the template's prefix), part of its name with this label (RGAA 11.1).
            'label' => 'Fin du lien',
            // RGAA 11.10: the expected format, tied to the field (aria-describedby).
            'help' => \sprintf(
                'De %d à %d caractères : lettres sans accent, chiffres et tirets. Majuscules, accents et espaces sont convertis. Évitez votre pseudo ou un mot courant : un tel lien se devine.',
                FriendBook::INVITATION_SLUG_MIN_LENGTH,
                FriendBook::INVITATION_SLUG_MAX_LENGTH,
            ),
            // An empty field gives '' rather than null: the setter takes a string, and the
            // Length constraint says what is missing.
            'empty_data' => '',
            'attr' => [
                // Next to the link's beginning, the field may narrow below its default width.
                'class' => 'min-w-0',
                'maxlength' => FriendBook::INVITATION_SLUG_MAX_LENGTH,
                'autocomplete' => 'off',
                'autocapitalize' => 'none',
                'spellcheck' => 'false',
            ],
        ]);

        // Before validation, so that FriendBook's rules check the slug that will be saved.
        $builder->get('invitationSlug')->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event): void {
            $data = $event->getData();
            // A string only: a field missing from the request comes as null (empty_data applies
            // later), and slug(null) would fail with a 500, not a 422. An array never gets here:
            // Form::submit() turns it into a transformation failure first.
            if (\is_string($data)) {
                $data = $this->slugger->slug($data)->lower()->toString();
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FriendBook::class,
        ]);
    }
}
