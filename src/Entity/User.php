<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[Uploadable]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cette adresse email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Email(message: 'L\'adresse email "{{ value }}" n\'est pas valide')]
    #[Assert\NotBlank(message: 'Il faut renseigner une adresse email')]
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\Length(
        min: 8,
        max: 255,
        minMessage: 'Votre mot de passe est trop court ({{ limit }} caractères minimum)',
        maxMessage: 'Votre mot de passe est trop long ({{ limit }} caractères maximum)'
    )]
    private ?string $password = null;

    /** It stores the plain password before hashing for EasyAdminSubscriber */
    private ?string $plainPassword = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Assert\NotBlank(message: 'Il faut renseigner un nom de famille')]
    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[Assert\NotBlank(message: 'Il faut renseigner un prénom')]
    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[UploadableField(mapping: 'avatar', fileNameProperty: 'avatar')]
    #[Assert\Image(
        allowLandscape: false,
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Le format de l\'image n\'est pas valide (formats acceptés : JPEG, PNG, WEBP)'
    )]
    private ?File $avatarFile = null;

    #[ORM\Column]
    private bool $isVerified = false;

    /**
     * @var Collection<int, Gift>
     */
    #[ORM\OneToMany(targetEntity: Gift::class, mappedBy: 'createdBy', orphanRemoval: true)]
    private Collection $gifts;

    /**
     * @var Collection<int, Section>
     */
    #[ORM\OneToMany(targetEntity: Section::class, mappedBy: 'createdBy')]
    private Collection $sections;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $avatar_updated_at = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarDefault = null;

    #[UploadableField(
        mapping: 'avatar',
        fileNameProperty: 'avatarDefault'
    )]
    private ?File $avatarDefaultFile = null;

    /**
     * @var Collection<int, Gift>
     */
    #[ORM\ManyToMany(targetEntity: Gift::class, inversedBy: 'favoritedBy')]
    #[ORM\JoinTable(name: 'user_favorite_gift')]
    private Collection $favoriteGifts;

    /*
     * @var Collection<int, Participant>
     */
    #[ORM\OneToMany(targetEntity: Participant::class, mappedBy: 'participantUser')]
    private Collection $participations;

    public function __construct()
    {
        $this->gifts = new ArrayCollection();
        $this->sections = new ArrayCollection();
        $this->favoriteGifts = new ArrayCollection();
        $this->participations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /** For EasyAdminSubscriber to access the plain password before hashing */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /** For EasyAdminSubscriber to set the plain password before hashing */
    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        unset($data["\0".self::class."\0avatarFile"]);
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getFullname(): string
    {
        return "$this->firstname $this->lastname";
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * The Vich field of the avatar to show: the one the user uploaded, or else the default
     * one generated at registration. Passed to vich_uploader_asset() / UploaderHelper::asset().
     */
    public function getAvatarField(): string
    {
        return $this->avatar ? 'avatarFile' : 'avatarDefaultFile';
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?File $avatarFile): static
    {
        $this->avatarFile = $avatarFile;

        if (null !== $avatarFile) {
            $this->avatar_updated_at = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection<int, Gift>
     */
    public function getGifts(): Collection
    {
        return $this->gifts;
    }

    public function addGift(Gift $gift): static
    {
        if (!$this->gifts->contains($gift)) {
            $this->gifts->add($gift);
            $gift->setCreatedBy($this);
        }

        return $this;
    }

    public function removeGift(Gift $gift): static
    {
        if ($this->gifts->removeElement($gift)) {
            // set the owning side to null (unless already changed)
            if ($gift->getCreatedBy() === $this) {
                $gift->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Section>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(Section $section): static
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setCreatedBy($this);
        }

        return $this;
    }

    public function removeSection(Section $section): static
    {
        if ($this->sections->removeElement($section)) {
            // set the owning side to null (unless already changed)
            if ($section->getCreatedBy() === $this) {
                $section->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function getAvatarUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->avatar_updated_at;
    }

    public function setAvatarUpdatedAt(\DateTimeImmutable $avatar_updated_at): static
    {
        $this->avatar_updated_at = $avatar_updated_at;

        return $this;
    }

    public function getAvatarDefault(): ?string
    {
        return $this->avatarDefault;
    }

    public function setAvatarDefault(string $avatarDefault): static
    {
        $this->avatarDefault = $avatarDefault;

        return $this;
    }

    public function getAvatarDefaultFile(): ?File
    {
        return $this->avatarDefaultFile;
    }

    public function setAvatarDefaultFile(?File $avatarDefaultFile): static
    {
        $this->avatarDefaultFile = $avatarDefaultFile;

        if (null !== $avatarDefaultFile) {
            $this->avatar_updated_at = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return Collection<int, Gift>
     */
    public function getFavoriteGifts(): Collection
    {
        return $this->favoriteGifts;
    }

    public function addFavoriteGift(Gift $favoriteGift): static
    {
        if (!$this->favoriteGifts->contains($favoriteGift)) {
            $this->favoriteGifts->add($favoriteGift);
        }

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participant $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setParticipantUser($this);
        }

        return $this;
    }

    public function removeFavoriteGift(Gift $favoriteGift): static
    {
        $this->favoriteGifts->removeElement($favoriteGift);

        return $this;
    }

    public function hasFavoriteGift(Gift $gift): bool
    {
        return $this->favoriteGifts->contains($gift);
    }

    public function removeParticipation(Participant $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getParticipantUser() === $this) {
                $participation->setParticipantUser(null);
            }
        }

        return $this;
    }
}
