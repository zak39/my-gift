<?php

namespace App\Entity;

use App\Repository\CrewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Friends who share a gift list: a couple making one for their child, a family...
 *
 * Its members are equals: each of them manages the list, invites their friends and renames
 * the crew; nobody takes anyone else out. Only its owner stands apart: they alone delete
 * it, and leave it once they passed it on to another member. The crew owns its sections
 * and every gift filed in them, whoever added it: like the owner of a personal list, no
 * member may offer one or see its pool. A member who leaves leaves their gifts behind.
 * When the owner deletes the crew, CrewLifecycle dissolves it and each gift goes back to
 * its author.
 */
#[ORM\Entity(repositoryClass: CrewRepository::class)]
class Crew
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Il faut donner un nom au groupe')]
    #[Assert\Length(
        max: 80,
        maxMessage: 'Le nom du groupe est trop long ({{ limit }} caractères maximum)',
    )]
    #[ORM\Column(length: 80)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * History only: who created the crew, never changed afterwards. Null once they deleted
     * their account. The role is $owner's.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $createdBy;

    /**
     * The one member who alone deletes the crew, and passes the role on (transferTo()),
     * its creator at first. Not to be confused with gift.is_owner / GiftOwnership, which
     * mean every member of the crew. Always a member: removeMember() refuses to let them go.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    /**
     * In the order they joined, the longest there first: the successor (getSuccessor())
     * comes from this order, whoever created or owns the crew.
     *
     * @var Collection<int, CrewMember>
     */
    #[ORM\OneToMany(targetEntity: CrewMember::class, mappedBy: 'crew', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['joinedAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $memberships;

    /**
     * Pending only: an invitation is deleted once answered or cancelled.
     *
     * @var Collection<int, CrewInvitation>
     */
    #[ORM\OneToMany(targetEntity: CrewInvitation::class, mappedBy: 'crew')]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $invitations;

    /**
     * @var Collection<int, Section>
     */
    #[ORM\OneToMany(targetEntity: Section::class, mappedBy: 'crew')]
    private Collection $sections;

    public function __construct(User $creator)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->createdBy = $creator;
        $this->owner = $creator;
        $this->memberships = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->sections = new ArrayCollection();

        $this->addMember($creator);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @return Collection<int, CrewMember>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    /**
     * The members by first name, as the pages list them. A copy: add or remove one through
     * addMember() and removeMember().
     *
     * @return list<User>
     */
    public function getMembers(): array
    {
        $members = $this->memberships->map(fn (CrewMember $membership) => $membership->getUser())->getValues();
        usort($members, fn (User $a, User $b) => strnatcasecmp($a->getFirstname() ?? '', $b->getFirstname() ?? ''));

        return $members;
    }

    /**
     * @return list<User>
     */
    public function getMembersExcept(User $user): array
    {
        return array_values(array_filter($this->getMembers(), fn (User $member) => $member !== $user));
    }

    public function hasMember(User $user): bool
    {
        return null !== $this->getMembershipOf($user);
    }

    /**
     * When the member joined, null for who is none.
     */
    public function getJoinedAt(User $member): ?\DateTimeImmutable
    {
        return $this->getMembershipOf($member)?->getJoinedAt();
    }

    public function addMember(User $user): static
    {
        if (!$this->hasMember($user)) {
            $this->memberships->add(new CrewMember($this, $user));
        }

        return $this;
    }

    /**
     * The gifts the member added stay in the list: they belong to the crew. The owner
     * never leaves, so a crew is never left empty: they pass it on first, or delete it.
     */
    public function removeMember(User $user): static
    {
        if ($user === $this->owner) {
            throw new \LogicException('Vous êtes propriétaire de ce groupe : confiez-le d\'abord à un autre membre pour le quitter, ou supprimez-le.');
        }

        $membership = $this->getMembershipOf($user);
        if (null !== $membership) {
            // orphanRemoval deletes its row.
            $this->memberships->removeElement($membership);
        }

        return $this;
    }

    private function getMembershipOf(User $user): ?CrewMember
    {
        foreach ($this->memberships as $membership) {
            if ($membership->getUser() === $user) {
                return $membership;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, CrewInvitation>
     */
    public function getInvitations(): Collection
    {
        return $this->invitations;
    }

    public function getInvitationFor(User $user): ?CrewInvitation
    {
        foreach ($this->invitations as $invitation) {
            if ($invitation->getInvitee() === $user) {
                return $invitation;
            }
        }

        return null;
    }

    /**
     * The checks the crew can make on its own; CrewLifecycle::invite() adds the friendship.
     */
    public function invite(User $invitedBy, User $invitee): CrewInvitation
    {
        if (!$this->hasMember($invitedBy)) {
            throw new \LogicException('Seuls les membres du groupe peuvent inviter.');
        }

        if ($this->hasMember($invitee)) {
            throw new \LogicException(\sprintf('%s fait déjà partie du groupe.', $invitee->getFirstname()));
        }

        if (null !== $this->getInvitationFor($invitee)) {
            throw new \LogicException(\sprintf('%s a déjà été invité·e dans le groupe.', $invitee->getFirstname()));
        }

        $invitation = new CrewInvitation($this, $invitedBy, $invitee);
        $this->invitations->add($invitation);

        return $invitation;
    }

    /**
     * @return Collection<int, Section>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    /**
     * The gifts across all its sections. Drafts are for the members' eyes only: a count
     * without them reads the same for the members and their friends.
     */
    public function getGiftCount(bool $includeDrafts = false): int
    {
        $count = 0;

        foreach ($this->sections as $section) {
            foreach ($section->getGifts() as $gift) {
                if ($includeDrafts || $gift->isPublic()) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    /**
     * Who takes the crew over when its owner deletes their account: the member who has
     * been there the longest. Null when the owner is alone, and the crew goes with them.
     */
    public function getSuccessor(): ?User
    {
        /** @var CrewMember|null $successor */
        $successor = $this->memberships->findFirst(
            fn (int $key, CrewMember $membership) => $membership->getUser() !== $this->owner
        );

        return $successor?->getUser();
    }

    /**
     * Another member becomes the owner, chosen by the owner or, when they delete their
     * account, the successor. The previous owner stays a member: they may now leave, as
     * any member. The creator does not change.
     */
    public function transferTo(User $member): static
    {
        if (!$this->hasMember($member)) {
            throw new \LogicException('Seul un membre du groupe peut en devenir propriétaire.');
        }

        if ($member === $this->owner) {
            throw new \LogicException(\sprintf('%s est déjà propriétaire du groupe.', $member->getFirstname()));
        }

        $this->owner = $member;

        return $this;
    }
}
