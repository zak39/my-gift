<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A user's place in a crew, since the day they joined it. The members are equals: the
 * date only tells who has been there the longest, who takes the crew over when its
 * creator deletes their account.
 */
#[ORM\Entity]
#[ORM\Table(name: 'crew_member')]
#[ORM\UniqueConstraint(name: 'UNIQ_CREW_MEMBER', columns: ['crew_id', 'user_id'])]
class CrewMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Crew $crew;

    /**
     * Eager: a membership is always read for its user (names, avatars), joined in the same
     * query rather than one query per member.
     */
    #[ORM\ManyToOne(fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    public function __construct(Crew $crew, User $user)
    {
        $this->crew = $crew;
        $this->user = $user;
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCrew(): Crew
    {
        return $this->crew;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
