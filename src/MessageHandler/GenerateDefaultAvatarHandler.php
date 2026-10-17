<?php

namespace App\MessageHandler;

use App\Message\GenerateDefaultAvatar;
use App\Repository\UserRepository;
use App\Services\DicebearAvatarGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateDefaultAvatarHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly DicebearAvatarGenerator $avatarGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(GenerateDefaultAvatar $message): void
    {
        $user = $this->userRepository->find($message->userId);

        // Deleted since, or given one in the meantime (an avatar reset): nothing left to do
        if (null === $user || null !== $user->getAvatarDefault()) {
            return;
        }

        $avatar = $this->avatarGenerator->generate($user->getFirstname())
            // Thrown for Messenger to retry it later (async transport's retry_strategy), then to
            // keep it in the failed transport: the user shows the placeholder meanwhile
            ?? throw new \RuntimeException(sprintf('DiceBear gave no default avatar for user %d.', $message->userId))
        ;

        $user->setAvatarDefaultFile($avatar);
        $this->entityManager->flush();
    }
}
