<?php

namespace App\Entity;

use App\Repository\SectionRepository;
use App\Validator\NotNullOutOfCrew;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SectionRepository::class)]
class Section
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Votre titre de section est trop courte ({{ min }} caractères min)',
        maxMessage: 'Votre titre de section est trop longue ({{ max }} caractères max)',
    )]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le titre de votre section est trop courte ({{ min }} caractères minimum)',
        maxMessage: 'Le titre de votre section est trop longue ({{ max }} caractères maximum)'
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Gift>
     */
    #[ORM\OneToMany(targetEntity: Gift::class, mappedBy: 'section')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $gifts;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    /**
     * Who created it: a personal section's owner; on a crew's, a trace emptied when its
     * user deletes their account, as Gift::$createdBy.
     */
    #[NotNullOutOfCrew]
    #[ORM\ManyToOne(inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    /**
     * Set when the section is part of a crew's list rather than of its creator's.
     */
    #[ORM\ManyToOne(inversedBy: 'sections')]
    private ?Crew $crew = null;

    public function __construct()
    {
        $this->gifts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Gift>
     */
    public function getGifts(): Collection
    {
        return $this->gifts;
    }

    /**
     * The gifts its list shows: all of them to its owners, drafts included; the published
     * ones to anyone else. A section's page, its menu entry and the list's header count
     * from here, so they agree.
     *
     * @return list<Gift>
     */
    public function getVisibleGifts(bool $includeDrafts): array
    {
        if ($includeDrafts) {
            return $this->gifts->getValues();
        }

        return $this->gifts->filter(fn (Gift $gift) => $gift->isPublic())->getValues();
    }

    public function addGift(Gift $gift): static
    {
        if (!$this->gifts->contains($gift)) {
            $this->gifts->add($gift);
            $gift->setSection($this);
        }

        return $this;
    }

    public function removeGift(Gift $gift): static
    {
        if ($this->gifts->removeElement($gift)) {
            // set the owning side to null (unless already changed)
            if ($gift->getSection() === $this) {
                $gift->setSection(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCrew(): ?Crew
    {
        return $this->crew;
    }

    public function setCrew(?Crew $crew): static
    {
        $this->crew = $crew;

        return $this;
    }

    /**
     * Who manages the section: the members of its crew, or else the user who created it.
     */
    public function isOwnedBy(User $user): bool
    {
        if (null !== $this->crew) {
            return $this->crew->hasMember($user);
        }

        return $this->createdBy === $user;
    }
}
