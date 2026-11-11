<?php

namespace App\Entity;

use App\Enum\FriendRelation;
use App\Repository\FriendBookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The social side of a user: friends, blocked users, pending requests and the invitation
 * link that lets someone ask them as a friend. User knows nothing about it, so the
 * friendship never changes User.
 *
 * The book reuses the id of its owner (derived identity): the book of user 42 is the
 * book 42, so FriendBookRepository::of() is a find() that the identity map answers
 * after the first call, and getReference() needs no query at all.
 */
#[ORM\Entity(repositoryClass: FriendBookRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_FRIEND_BOOK_INVITATION_SLUG', fields: ['invitationSlug'])]
#[UniqueEntity(fields: ['invitationSlug'], message: 'Ce lien est déjà pris : choisissez-en un autre')]
class FriendBook
{
    /**
     * Words of lowercase letters and digits joined by single dashes: what AsciiSlugger gives,
     * and the random slugs too (InvitationSlugGenerator). Without anchors, for the route's
     * requirement; INVITATION_SLUG_REGEXP anchors it for a whole value.
     */
    public const INVITATION_SLUG_PATTERN = '[a-z0-9]+(?:-[a-z0-9]+)*';

    /** D: a final line break does not slip past $, as for User's pseudo. */
    public const INVITATION_SLUG_REGEXP = '/^'.self::INVITATION_SLUG_PATTERN.'$/D';

    // Not 3, like a pseudo: the slug is the link's only secret, and a few thousand short ones
    // are all tried in minutes.
    public const INVITATION_SLUG_MIN_LENGTH = 8;
    public const INVITATION_SLUG_MAX_LENGTH = 50;

    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $owner;

    /**
     * The end of the owner's invitation link, /invitation/{slug}: random at first, or one
     * they chose. Whoever holds the link may ask the owner as a friend, so changing it ends
     * the previous one at once: the slug is the link's only secret. The one given up is free
     * again, for anyone to choose: the page names whoever the link leads to, and each
     * request waits for their answer.
     */
    #[Assert\Length(
        min: self::INVITATION_SLUG_MIN_LENGTH,
        max: self::INVITATION_SLUG_MAX_LENGTH,
        minMessage: 'Le lien est trop court ({{ limit }} caractères minimum)',
        maxMessage: 'Le lien est trop long ({{ limit }} caractères maximum)',
    )]
    #[Assert\Regex(
        pattern: self::INVITATION_SLUG_REGEXP,
        message: 'Le lien ne peut contenir que des lettres sans accent, des chiffres et des tirets entre les mots',
    )]
    #[ORM\Column(length: self::INVITATION_SLUG_MAX_LENGTH)]
    private string $invitationSlug;

    /**
     * Both ways: befriend() and unfriend() update the two books together, so
     * $a->hasFriend($b) and $b->hasFriend($a) always agree.
     *
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'user_friend')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'owner_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'friend_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['pseudo' => 'ASC'])]
    private Collection $friends;

    /**
     * One way only: the blocked user's book keeps no trace of it.
     *
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'user_blocked')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'owner_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'blocked_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $blockedUsers;

    /**
     * @var Collection<int, FriendRequest>
     */
    #[ORM\OneToMany(targetEntity: FriendRequest::class, mappedBy: 'sender')]
    private Collection $sentRequests;

    /**
     * @var Collection<int, FriendRequest>
     */
    #[ORM\OneToMany(targetEntity: FriendRequest::class, mappedBy: 'recipient')]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $receivedRequests;

    /**
     * @param string $invitationSlug a free one, from InvitationSlugGenerator
     */
    public function __construct(User $owner, string $invitationSlug)
    {
        $this->owner = $owner;
        $this->invitationSlug = $invitationSlug;
        $this->friends = new ArrayCollection();
        $this->blockedUsers = new ArrayCollection();
        $this->sentRequests = new ArrayCollection();
        $this->receivedRequests = new ArrayCollection();
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getInvitationSlug(): string
    {
        return $this->invitationSlug;
    }

    public function setInvitationSlug(string $invitationSlug): static
    {
        $this->invitationSlug = $invitationSlug;

        return $this;
    }

    public function relationWith(User $user): FriendRelation
    {
        return match (true) {
            $this->hasFriend($user) => FriendRelation::FRIEND,
            $this->hasBlocked($user) => FriendRelation::BLOCKED,
            null !== $this->getRequestReceivedFrom($user) => FriendRelation::REQUEST_RECEIVED,
            null !== $this->getRequestSentTo($user) => FriendRelation::REQUEST_SENT,
            default => FriendRelation::NONE,
        };
    }

    /**
     * @return Collection<int, User>
     */
    public function getFriends(): Collection
    {
        return $this->friends;
    }

    public function hasFriend(User $user): bool
    {
        return $this->friends->contains($user);
    }

    public function befriend(self $other): static
    {
        // Both books: a class may reach the private properties of its other instances.
        if (!$this->friends->contains($other->owner)) {
            $this->friends->add($other->owner);
        }

        if (!$other->friends->contains($this->owner)) {
            $other->friends->add($this->owner);
        }

        return $this;
    }

    public function unfriend(self $other): static
    {
        $this->friends->removeElement($other->owner);
        $other->friends->removeElement($this->owner);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getBlockedUsers(): Collection
    {
        return $this->blockedUsers;
    }

    public function hasBlocked(User $user): bool
    {
        return $this->blockedUsers->contains($user);
    }

    public function block(User $user): static
    {
        if (!$this->blockedUsers->contains($user)) {
            $this->blockedUsers->add($user);
        }

        return $this;
    }

    public function unblock(User $user): static
    {
        $this->blockedUsers->removeElement($user);

        return $this;
    }

    /**
     * @return Collection<int, FriendRequest>
     */
    public function getSentRequests(): Collection
    {
        return $this->sentRequests;
    }

    /**
     * @return Collection<int, FriendRequest>
     */
    public function getReceivedRequests(): Collection
    {
        return $this->receivedRequests;
    }

    public function getRequestSentTo(User $user): ?FriendRequest
    {
        foreach ($this->sentRequests as $friendRequest) {
            if ($friendRequest->getRecipient()->getOwner() === $user) {
                return $friendRequest;
            }
        }

        return null;
    }

    public function getRequestReceivedFrom(User $user): ?FriendRequest
    {
        foreach ($this->receivedRequests as $friendRequest) {
            if ($friendRequest->getSender()->getOwner() === $user) {
                return $friendRequest;
            }
        }

        return null;
    }
}
