<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:set-admin',
    description: 'Sets a user as an admin by their ID',
    help: 'Sets a user as an admin by their ID',
)]
class UserSetAdminCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'ID of the user to set as admin', name: 'userId')]
        ?int $userId = null,
    ): int {
        if (null === $userId) {
            $io->error('User ID must be provided.');

            return Command::FAILURE;
        }

        $user = $this->userRepository->find($userId);

        if (!$user) {
            $io->error(sprintf('User with ID %d not found.', $userId));

            return Command::FAILURE;
        }

        $user->setRoles([...$user->getRoles(), 'ROLE_ADMIN']);
        $this->entityManager->flush();

        $io->success(sprintf('User %s (%d) has been set as admin.', $user->getFullName(), $userId));

        return Command::SUCCESS;
    }
}
