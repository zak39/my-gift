<?php

namespace App\Entity;

use App\Enum\GiftStatus;
use App\Repository\GiftRepository;
use App\ValueObject\GiftOwnership;
use App\ValueObject\GiftPool;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

#[ORM\Entity(repositoryClass: GiftRepository::class)]
#[Uploadable]
class Gift
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull(message: 'Vous devez renseigner le titre de votre cadeau')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Votre titre est trop court ! (2 caractères minimum)',
        maxMessage: 'Votre titre est trop long ! (255 caractères max)'
    )]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Assert\Length(
        min: 2,
        max: 255,
        maxMessage: 'Votre description est trop longue ! (255 caractères max)'
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[UploadableField(mapping: 'imageGift', fileNameProperty: 'image')]
    #[Assert\Image(
        allowLandscape: false,
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Le format de l\'image n\'est pas valide (formats acceptés : JPEG, PNG, WEBP)'
    )]
    private ?File $imageFile = null;

    #[Assert\NotBlank(message: 'Vous devez renseigner le prix de votre cadeau')]
    #[Assert\Positive(message: 'Le prix de votre cadeau doit être possitif')]
    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\OneToMany(targetEntity: Participant::class, mappedBy: 'gift', orphanRemoval: true)]
    private Collection $participants;

    #[ORM\ManyToOne(inversedBy: 'gifts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private ?bool $isPublic = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(enumType: GiftStatus::class)]
    private ?GiftStatus $status = null;

    #[Assert\Url(
        message: 'Le champ doit être une URL avec "http://..." ou "https://...".',
        requireTld: true,
        tldMessage: 'Le nom de domaine de votre URL doit être valide (ex: .com, .fr, .net...).'
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $link = null;

    #[ORM\ManyToOne(inversedBy: 'gifts')]
    private ?Section $section = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updateAt = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favoriteGifts')]
    private Collection $favoritedBy;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $givenAt = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->favoritedBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): static
    {
        $this->imageFile = $imageFile;

        // Force VichUploaderBundle to process the file by resetting the filename
        if (null !== $imageFile) {
            $this->updateAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setGift($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getGift() === $this) {
                $participant->setGift(null);
            }
        }

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

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): ?GiftStatus
    {
        return $this->status;
    }

    public function setStatus(GiftStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getSection(): ?Section
    {
        return $this->section;
    }

    public function setSection(?Section $section): static
    {
        $this->section = $section;

        return $this;
    }

    /**
     * The crew whose list the gift is filed in, if any.
     */
    public function getCrew(): ?Crew
    {
        return $this->section?->getCrew();
    }

    /**
     * The owners manage the gift and stand on the receiving side: they may not offer it,
     * and its pool keeps its surprise for them. Filed in a crew's list, the gift belongs
     * to the crew's members, whoever added it; anywhere else, to its creator.
     */
    public function getOwnership(): GiftOwnership
    {
        return new GiftOwnership($this->createdBy, $this->getCrew());
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->updateAt;
    }

    public function setUpdateAt(?\DateTimeImmutable $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFavoritedBy(): Collection
    {
        return $this->favoritedBy;
    }

    public function addFavoritedBy(User $favoritedBy): static
    {
        if (!$this->favoritedBy->contains($favoritedBy)) {
            $this->favoritedBy->add($favoritedBy);
            $favoritedBy->addFavoriteGift($this);
        }

        return $this;
    }

    public function removeFavoritedBy(User $favoritedBy): static
    {
        if ($this->favoritedBy->removeElement($favoritedBy)) {
            $favoritedBy->removeFavoriteGift($this);
        }

        return $this;
    }

    public function getGivenAt(): ?\DateTimeImmutable
    {
        return $this->givenAt;
    }

    public function setGivenAt(?\DateTimeImmutable $givenAt): static
    {
        $this->givenAt = $givenAt;

        return $this;
    }

    public function getParticipant(User $user): ?Participant
    {
        foreach ($this->participants as $participant) {
            if ($participant->getParticipantUser() === $user) {
                return $participant;
            }
        }

        return null;
    }

    /**
     * The money its friends put together towards it, empty when nobody chipped in.
     */
    public function getPool(): GiftPool
    {
        return new GiftPool($this->price, $this->participants, $this->status);
    }
}
