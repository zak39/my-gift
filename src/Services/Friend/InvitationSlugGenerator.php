<?php

namespace App\Services\Friend;

use Symfony\Component\String\ByteString;

/**
 * The random slug of an invitation link (FriendBook::$invitationSlug): given to a new book
 * (CreateFriendBookListener), and drawn again when its owner asks for a new link.
 */
final class InvitationSlugGenerator
{
    /**
     * Lowercase letters and digits but the look-alikes, 0 and o, 1, i and l: a link read
     * aloud or copied by hand is typed right. 31 characters, so 12 of them give about 8×10^17
     * links: nobody finds one by trying, and two books all but never draw the same one (the
     * unique index stays the backstop).
     */
    public const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    public const LENGTH = 12;

    public function __invoke(): string
    {
        return ByteString::fromRandom(self::LENGTH, self::ALPHABET)->toString();
    }
}
