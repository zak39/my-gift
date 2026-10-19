<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Files to remove once the database has agreed: a removed file cannot be rolled back.
 *
 * MoveGiftImageListener adds a moved image's original and a deleted crew's folder, and
 * removes them after a flush committed on its own. Inside a wider transaction, the flush
 * does not know the outcome: whoever opened the transaction calls removeAll() after its
 * commit, or forget() when it rolls back (UserLifecycle::delete()).
 */
final class PendingFileRemovals implements ResetInterface
{
    /** @var list<string> */
    private array $paths = [];

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function add(string $path): void
    {
        $this->paths[] = $path;
    }

    /**
     * Never throws: the database has already agreed, so a file that resists is a leftover
     * to clean up, logged, not a failure of what was committed. Each path is tried, one
     * failing does not keep the others.
     */
    public function removeAll(): void
    {
        $paths = $this->paths;
        $this->paths = [];

        foreach ($paths as $path) {
            try {
                $this->filesystem->remove($path);
            } catch (IOExceptionInterface $exception) {
                $this->logger->error('File left behind after a commit', ['path' => $path, 'exception' => $exception]);
            }
        }
    }

    public function forget(): void
    {
        $this->paths = [];
    }

    /**
     * Between two requests or two messages of a worker: what a failed flush left pending
     * must not be removed by the next one.
     */
    public function reset(): void
    {
        $this->forget();
    }
}
