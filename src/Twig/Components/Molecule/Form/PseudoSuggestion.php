<?php

namespace App\Twig\Components\Molecule\Form;

use App\Services\User\SuggestPseudo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Under a pseudo field refused because the pseudo is taken (UniqueEntity), free ones to
 * take instead (SuggestPseudo). Renders nothing for any other error, or none.
 */
#[AsTwigComponent]
final class PseudoSuggestion
{
    /** @var list<string> */
    public array $suggestions = [];

    public function __construct(
        private readonly SuggestPseudo $suggestPseudo,
    ) {
    }

    public function mount(FormView $field): void
    {
        foreach ($field->vars['errors'] as $error) {
            /** @var FormError $error */
            $cause = $error->getCause();

            if ($cause instanceof ConstraintViolationInterface && UniqueEntity::NOT_UNIQUE_ERROR === $cause->getCode()) {
                $this->suggestions = ($this->suggestPseudo)((string) $field->vars['value']);

                return;
            }
        }
    }
}
