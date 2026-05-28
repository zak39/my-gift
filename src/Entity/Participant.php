<?php

namespace App\Entity;

use App\Enum\ParticipantStatus;
use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'participant')]
    private Collection $participantUser;

    #[ORM\Column(nullable: true)]
    private ?float $amount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $givenDate = null;

    #[ORM\Column]
    private ?bool $anonymous = null;

    #[ORM\Column(enumType: ParticipantStatus::class)]
    private ?ParticipantStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?Gift $gift = null;

    public function __construct()
    {
        $this->participantUser = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, User>
     */
    public function getParticipantUser(): Collection
    {
        return $this->participantUser;
    }

    public function addParticipantUser(User $participantUser): static
    {
        if (!$this->participantUser->contains($participantUser)) {
            $this->participantUser->add($participantUser);
            $participantUser->setParticipant($this);
        }

        return $this;
    }

    public function removeParticipantUser(User $participantUser): static
    {
        if ($this->participantUser->removeElement($participantUser)) {
            // set the owning side to null (unless already changed)
            if ($participantUser->getParticipant() === $this) {
                $participantUser->setParticipant(null);
            }
        }

        return $this;
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

    public function getGivenDate(): ?\DateTimeImmutable
    {
        return $this->givenDate;
    }

    public function setGivenDate(\DateTimeImmutable $givenDate): static
    {
        $this->givenDate = $givenDate;

        return $this;
    }

    public function isAnonymous(): ?bool
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
}
