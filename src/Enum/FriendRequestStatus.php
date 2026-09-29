<?php

namespace App\Enum;

/**
 * Only PENDING is ever stored: FriendRequestLifecycle deletes a request as soon as it is
 * answered or cancelled. ACCEPTED and ENDED exist so that the workflow runs the guards
 * and events of the transitions leading there.
 */
enum FriendRequestStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case ENDED = 'ended';
}
