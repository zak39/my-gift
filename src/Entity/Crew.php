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
 * the crew; nobody takes anyone else out. Only its creator stands apart: they never leave
 * it, and they alone delete it. The crew owns its sections and every gift filed in them,
 * whoever added it: like the owner of a personal list, no member may offer one or see its
 * pool. A member who leaves leaves their gifts behind. When the creator deletes the crew,
 * CrewLifecycle dissolves it and each gift goes back to its author.
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
     * Always a member: removeMember() refuses to let them go.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    /**
     * In the order they joined, the creator first.
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

    public function getCreatedBy(): User
    {
        return $this->createdBy;
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

    public function addMember(User $user): static
    {
        if (!$this->hasMember($user)) {
            $this->memberships->add(new CrewMember($this, $user));
        }

        return $this;
    }

    /**
     * The gifts the member added stay in the list: they belong to the crew. The creator
     * never leaves, so a crew is never left empty: deleting it is their way out.
     */
    public function removeMember(User $user): static
    {
        if ($user === $this->createdBy) {
            throw new \LogicException('Vous avez créé ce groupe : vous ne pouvez pas le quitter, mais vous pouvez le supprimer.');
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
}
