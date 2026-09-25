<?php

namespace App\Twig\Components\Organism\Gift;

use App\Entity\Gift;
use App\Form\GiftType;
use App\Services\OpenGraphApi;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class Form
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?Gift $initialFormData = null;

    #[LiveProp(writable: true, onUpdated: 'fetchOpenGraphData')]
    public ?string $link = null;

    #[LiveProp]
    public bool $openGraphFailed = false;

    public function __construct(
        private FormFactoryInterface $formFactory,
        private OpenGraphApi $openGraphApi,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(GiftType::class, $this->initialFormData);
    }

    #[PostMount]
    public function initializeLinkFromEntity(): void
    {
        if (null === $this->link) {
            $this->link = $this->initialFormData?->getLink();
        }
    }

    #[LiveAction]
    public function fetchOpenGraphData(): void
    {
        $this->openGraphFailed = false;

        if (!$this->link) {
            return;
        }

        $url = str_starts_with($this->link, 'http') ? $this->link : 'https://'.$this->link;

        try {
            $dto = $this->openGraphApi->getData($url);
        } catch (\Exception $e) {
            $this->openGraphFailed = true;

            return;
        }

        if ($dto->hasData()) {
            $this->formValues['title'] = $dto->title;
            $this->formValues['description'] = $dto->description;
            $this->formValues['openGraphImageUrl'] = $dto->image;
        } else {
            $this->openGraphFailed = true;
        }
    }
}
