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

    public function hasData(): bool
    {
        return
            '' !== $this->title
            || '' !== $this->type
            || '' !== $this->url
            || '' !== $this->siteName
            || '' !== $this->description
            || '' !== $this->image
        ;
    }
}
