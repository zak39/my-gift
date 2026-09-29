<?php

namespace App\Enum;

/**
 * What links a user to another one, seen from the first user's FriendBook. Nothing
 * is stored: FriendBook::relationWith() works it out from its collections.
 *
 * A block set by the other user is not a case on purpose: it lives in the other
 * book, and the blocked user must see a stranger.
 */
enum FriendRelation
{
    case NONE;
    case FRIEND;
    case REQUEST_SENT;
    case REQUEST_RECEIVED;
    case BLOCKED;
}
