<?php

namespace App\Twig\Components\Organism\Gift;

use App\Entity\Gift;
use App\Form\GiftType;
use App\Services\Dto\ProductPageDto;
use App\Services\ProductPage\ProductPageReader;
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

    /**
     * What the last link filled in, to tell it from what the user typed: a field still
     * holding it is the next link's to fill, or to empty.
     *
     * @var array<string, ?string>
     */
    #[LiveProp]
    public array $prefilled = [];

    /** Back to the list the gift is created in: the user's own, or a crew's. */
    #[LiveProp]
    public string $cancelUrl = '';

    /** The page the form was opened from, to go back to once the gift is created (Atomic:BackField). */
    #[LiveProp]
    public ?string $back = null;

    public function __construct(
        private FormFactoryInterface $formFactory,
        private ProductPageReader $productPageReader,
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
            $product = $this->productPageReader->read($url);
        } catch (\Exception $e) {
            $this->openGraphFailed = true;

            // What the previous link filled in is not this page's
            $product = new ProductPageDto();
        }

        // Never typed: the image always is the page's, or none
        $this->formValues['openGraphImageUrl'] = $product->image;

        $fields = [
            'title' => $product->title,
            'description' => $product->description,
            // As the field shows it in French: 49,99
            'price' => null === $product->price ? null : number_format($product->price, 2, ',', ''),
        ];

        foreach ($fields as $field => $value) {
            $current = $this->formValues[$field] ?? null;
            $typed = null !== $current && '' !== $current && $current !== ($this->prefilled[$field] ?? null);

            // A field the page lacks keeps what the user typed
            $this->formValues[$field] = $value ?? ($typed ? $current : null);
            $this->prefilled[$field] = $value;
        }
    }
}
