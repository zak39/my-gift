<?php

namespace App\Entity;

use App\Enum\CrewInvitationStatus;
use App\Repository\CrewInvitationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A member's invitation to one of their friends, waiting for an answer and nothing more:
 * once accepted, refused or cancelled, CrewLifecycle deletes it. Hence one row at most
 * per crew and invitee.
 */
#[ORM\Entity(repositoryClass: CrewInvitationRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CREW_INVITATION', columns: ['crew_id', 'invitee_id'])]
class CrewInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Crew $crew;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $invitedBy;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $invitee;

    /**
     * The marking of the `crew_invitation` workflow: its marking store reads and writes
     * the enum through getStatus() and setStatus().
     */
    #[ORM\Column(enumType: CrewInvitationStatus::class)]
    private CrewInvitationStatus $status = CrewInvitationStatus::PENDING;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * Through Crew::invite(), which checks who may be invited.
     */
    public function __construct(Crew $crew, User $invitedBy, User $invitee)
    {
        $this->crew = $crew;
        $this->invitedBy = $invitedBy;
        $this->invitee = $invitee;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCrew(): Crew
    {
        return $this->crew;
    }

    public function getInvitedBy(): User
    {
        return $this->invitedBy;
    }

    public function getInvitee(): User
    {
        return $this->invitee;
    }

    public function getStatus(): CrewInvitationStatus
    {
        return $this->status;
    }

    public function setStatus(CrewInvitationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
