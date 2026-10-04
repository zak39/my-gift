<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:search',
    description: 'Search for users by their first or last name',
    help: 'Lists the users whose first or last name contains the pattern, with their ID',
)]
class UserSearchCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Pattern to search for in user full names', name: 'pattern')]
        ?string $pattern = null,
    ): int {
        if (null === $pattern) {
            $io->error('A pattern must be provided.');

            return Command::FAILURE;
        }

        $users = $this->userRepository->findByFullNamePattern($pattern);

        if (empty($users)) {
            $io->error(sprintf('User with name matching "%s" not found.', $pattern));

            return Command::FAILURE;
        }

        $io->table(
            ['ID', 'Nom', 'Email', 'Admin'],
            array_map(fn (User $user) => [
                $user->getId(),
                $user->getFullname(),
                $user->getEmail(),
                in_array('ROLE_ADMIN', $user->getRoles()) ? 'Yes' : 'No',
            ], $users)
        );

        return Command::SUCCESS;
    }
}
