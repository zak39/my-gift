<?php

namespace App\Services\Exception;

/**
 * GiftLifecycle refuses an action its workflow does not allow in the gift's current
 * state, or a user who does not take part in it: a stale page or a forged request.
 * Its message is meant for the user; the Gift\ controllers show it as an error flash.
 */
final class GiftActionRefusedException extends \DomainException
{
}
