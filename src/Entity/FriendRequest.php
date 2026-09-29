<?php

namespace App\Entity;

use App\Enum\FriendRequestStatus;
use App\Repository\FriendRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A request waiting for an answer, and nothing more: once accepted, refused or
 * cancelled, FriendRequestLifecycle deletes it. The friendship itself lives in
 * FriendBook::$friends. It links two books, not two users, so the pending requests
 * live in the books too.
 */
#[ORM\Entity(repositoryClass: FriendRequestRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_FRIEND_REQUEST', columns: ['sender_id', 'recipient_id'])]
class FriendRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sentRequests')]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'owner_id', nullable: false)]
    private ?FriendBook $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receivedRequests')]
    #[ORM\JoinColumn(name: 'recipient_id', referencedColumnName: 'owner_id', nullable: false)]
    private ?FriendBook $recipient = null;

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

    public function getSender(): ?FriendBook
    {
        return $this->sender;
    }

    public function setSender(?FriendBook $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getRecipient(): ?FriendBook
    {
        return $this->recipient;
    }

    public function setRecipient(?FriendBook $recipient): static
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
