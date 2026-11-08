<?php

namespace App\Entity;

use App\Enum\ParticipantStatus;
use App\Repository\ParticipantRepository;
use App\Validator\RemainingAmount;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[RemainingAmount]
    #[Assert\Positive(message: 'Le montant doit être positif')]
    private ?float $amount = null;

    #[ORM\Column]
    private ?bool $anonymous = null;

    #[ORM\Column(enumType: ParticipantStatus::class)]
    private ?ParticipantStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Gift $gift = null;

    /**
     * Null only once its user deleted their account: see detachDeletedUser().
     */
    #[ORM\ManyToOne(inversedBy: 'participations')]
    private ?User $participantUser = null;

    /**
     * When the participant's account was deleted. The database refuses a participation
     * without a user unless this date is set (a CHECK constraint, written in the migration
     * since Doctrine maps none): a user missing by mistake fails loudly instead of being
     * shown as deleted.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $userDeletedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\When(
        expression: 'this.isAnonymous()',
        constraints: [
            new Assert\NotBlank(
                message: 'Vous devez saisir un pseudo pour qu\'on ne vous ne reconnaisse pas.'
            ),
        ]
    )]
    private ?string $pseudoAnonyme = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function isAnonymous(): ?bool
    {
        return $this->anonymous;
    }

    public function getAnonymous(): ?bool
    {
        return $this->anonymous;
    }

    public function setAnonymous(bool $anonymous): static
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    public function getStatus(): ?ParticipantStatus
    {
        return $this->status;
    }

    public function setStatus(ParticipantStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getGift(): ?Gift
    {
        return $this->gift;
    }

    public function setGift(?Gift $gift): static
    {
        $this->gift = $gift;

        return $this;
    }

    public function getParticipantUser(): ?User
    {
        return $this->participantUser;
    }

    public function setParticipantUser(User $participantUser): static
    {
        $this->participantUser = $participantUser;

        return $this;
    }

    /**
     * Keeps the participation in the gift's history once its user deleted their account,
     * with no trace of who they were.
     */
    public function detachDeletedUser(): static
    {
        $this->participantUser = null;
        $this->userDeletedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isFromDeletedUser(): bool
    {
        return null !== $this->userDeletedAt;
    }

    public function getUserDeletedAt(): ?\DateTimeImmutable
    {
        return $this->userDeletedAt;
    }

    public function getPseudoAnonyme(): ?string
    {
        return $this->pseudoAnonyme;
    }

    public function setPseudoAnonyme(?string $pseudoAnonyme): static
    {
        $this->pseudoAnonyme = $pseudoAnonyme;

        return $this;
    }

    /**
     * An anonymous participation keeps its pseudonym once its user is deleted: it never
     * showed who they were.
     */
    public function getDisplayName(): string
    {
        return match (true) {
            $this->isAnonymous() => $this->getPseudoAnonyme(),
            $this->isFromDeletedUser() => 'Utilisateur supprimé',
            default => $this->getParticipantUser()->getPseudo(),
        };
    }
}
