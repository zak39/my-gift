<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * Asks the worker for a new user's DiceBear avatar: out of the registration request, which
 * neither waits for DiceBear nor fails with it, and retried when DiceBear is down.
 */
#[AsMessage('async')]
final readonly class GenerateDefaultAvatar
{
    public function __construct(
        public int $userId,
    ) {
    }
}
