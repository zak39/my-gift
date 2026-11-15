<?php

namespace App\Services\Dto;

final class OpenGraphDto
{
    public readonly ?string $title;
    public readonly ?string $type;
    public readonly ?string $url;
    public readonly ?string $siteName;
    public readonly ?string $description;
    public readonly ?string $image;
    public readonly array $raw;

    public function __construct(array $data = [])
    {
        $this->title = $data['og:title'] ?? null;
        $this->type = $data['og:type'] ?? null;
        $this->url = $data['og:url'] ?? null;
        $this->siteName = $data['og:site_name'] ?? null;
        $this->description = $data['og:description'] ?? null;
        $this->image = $data['og:image'] ?? null;
        $this->raw = $data;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'type' => $this->type,
            'url' => $this->url,
            'site_name' => $this->siteName,
            'description' => $this->description,
            'image' => $this->image,
        ];
    }

    /**
     * Whether the page gives anything a gift's card is filled with. Its other tags
     * (type, url, site name) fill nothing: a page with only those is not readable.
     */
    public function hasGiftData(): bool
    {
        return
            null !== $this->title
            || null !== $this->description
            || null !== $this->image
        ;
    }
}
