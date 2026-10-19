<?php

namespace App\EventListener;

use App\Entity\Crew;
use App\Entity\Gift;
use App\Entity\Section;
use App\Entity\User;
use App\Services\PendingFileRemovals;
use App\Vich\GiftDirectoryNamer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Keeps a gift's image where GiftDirectoryNamer files it, whoever changes its crew or its
 * author: edited into or out of a crew's list, released when its section or its crew is
 * deleted, edited in the admin. One place for every move, rather than one call per service.
 * A crew deleted, from the site or the admin, takes its folder along.
 *
 * The file is copied while the gift is flushed; the original, like a deleted crew's folder,
 * goes once the database has agreed (PendingFileRemovals): a flush that fails leaves the
 * gift and its image as they were.
 */
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Gift::class)]
#[AsDoctrineListener(event: Events::preFlush)]
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class MoveGiftImageListener
{
    public function __construct(
        private readonly GiftDirectoryNamer $directoryNamer,
        private readonly PendingFileRemovals $pendingFileRemovals,
        private readonly Filesystem $filesystem,
        #[Autowire('%app.data_dir%')]
        private readonly string $dataDirectory,
    ) {
    }

    public function preUpdate(Gift $gift, PreUpdateEventArgs $event): void
    {
        // Most updates (a title, a reservation) move nothing.
        if (!$event->hasChangedField('section') && !$event->hasChangedField('createdBy')) {
            return;
        }

        /** @var Section|null $previousSection */
        $previousSection = $event->hasChangedField('section') ? $event->getOldValue('section') : $gift->getSection();
        /** @var User|null $previousAuthor */
        $previousAuthor = $event->hasChangedField('createdBy') ? $event->getOldValue('createdBy') : $gift->getCreatedBy();

        $from = $this->directoryNamer->directoryFor($previousSection?->getCrew(), $previousAuthor);
        $to = $this->directoryNamer->directoryFor($gift->getCrew(), $gift->getCreatedBy());
        $image = $event->hasChangedField('image') ? $event->getOldValue('image') : $gift->getImage();

        if ($from === $to || null === $image) {
            return;
        }

        $original = "$this->dataDirectory/$from/$image";

        if (!$this->filesystem->exists($original)) {
            return;
        }

        // A new image uploaded in the same flush: Vich files it straight into the new folder.
        if (!$event->hasChangedField('image') && null === $gift->getImageFile()) {
            // copy() creates the folder when needed.
            $this->filesystem->copy($original, "$this->dataDirectory/$to/$image");
        }

        $this->pendingFileRemovals->add($original);
    }

    /**
     * A flush on its own starts with nothing pending: anything left comes from a flush
     * that failed, its paths noted before the database refused (a crew whose deletion was
     * rolled back still needs its folder). Inside a wider transaction, what earlier flushes
     * noted waits for its outcome.
     */
    public function preFlush(PreFlushEventArgs $event): void
    {
        if (!$event->getObjectManager()->getConnection()->isTransactionActive()) {
            $this->pendingFileRemovals->forget();
        }
    }

    /**
     * A crew deleted by this flush: its gifts go back to their authors on the same flush
     * (CrewLifecycle::delete()), their images along, what is left is the crew's. Read here
     * rather than on preRemove, which runs at remove(), before preFlush has emptied the
     * queue.
     */
    public function onFlush(OnFlushEventArgs $event): void
    {
        foreach ($event->getObjectManager()->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Crew) {
                $this->pendingFileRemovals->add("$this->dataDirectory/{$this->directoryNamer->crewDirectory($entity)}");
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        // Inside a wider transaction, the flush does not know the outcome: whoever opened
        // it removes the files after its commit (PendingFileRemovals).
        if (!$event->getObjectManager()->getConnection()->isTransactionActive()) {
            $this->pendingFileRemovals->removeAll();
        }
    }
}
