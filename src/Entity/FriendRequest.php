<?php

namespace App\Entity;

use App\Enum\FriendRequestStatus;
use App\Repository\FriendRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A request waiting for an answer, and nothing more: once accepted, refused or
 * cancelled, FriendRequestLifecycle deletes it. The friendship itself lives in
 * User::$friends.
 */
#[ORM\Entity(repositoryClass: FriendRequestRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_FRIEND_REQUEST', columns: ['sender_id', 'recipient_id'])]
class FriendRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sentFriendRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receivedFriendRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $recipient = null;

    #[ORM\Column(enumType: FriendRequestStatus::class)]
    private FriendRequestStatus $status = FriendRequestStatus::PENDING;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getStatus(): FriendRequestStatus
    {
        return $this->status;
    }

    public function setStatus(FriendRequestStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
