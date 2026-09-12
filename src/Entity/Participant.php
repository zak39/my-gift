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

    #[ORM\ManyToOne(inversedBy: 'participations')]
    private ?User $participantUser = null;

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

    public function setParticipantUser(?User $participantUser): static
    {
        $this->participantUser = $participantUser;

        return $this;
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

    public function getDisplayName(): string
    {
        return $this->isAnonymous()
            ? $this->getPseudoAnonyme()
            : $this->getParticipantUser()->getFullname()
        ;
    }
}
