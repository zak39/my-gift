<?php

namespace App\Enum;

/**
 * The places of the `crew_invitation` workflow. Only PENDING is ever stored: CrewLifecycle
 * deletes an invitation as soon as it is answered or cancelled, as FriendRequestLifecycle
 * does for a friend request. ACCEPTED and ENDED exist so that the workflow runs the guards
 * and events of the transitions leading there.
 */
enum CrewInvitationStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case ENDED = 'ended';
}
