<?php

namespace App\Enum;

/**
 * Why an account went: the confirmation email says what it is told.
 */
enum AccountDeletionReason
{
    /** From the profile, or by an admin. */
    case Requested;
    /** By the purge, once the notice of the warning email ran out. */
    case Inactivity;
    /** By the purge: an email address never confirmed gets no email. */
    case Unverified;
}
