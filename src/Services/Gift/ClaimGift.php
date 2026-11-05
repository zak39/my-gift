<?php

namespace App\Services\Gift;

use App\Entity\Gift;
use App\Entity\User;
use App\Repository\GiftRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * A member takes over a crew gift whose author deleted their account: they become its
 * author, with an author's rights, so that it goes back to them rather than away when its
 * section or its crew is deleted (ReleaseGifts), and they get its author's emails. Its
 * image stays put: GiftDirectoryNamer files a crew gift under its crew, whoever added it.
 *
 * The gift's row locked till the commit and read again first: two members claiming it,
 * or its section deleted (ReleaseGifts locks it too), at the same moment run one after the
 * other, the second one seeing what the first did. The rule is gift.can_claim's, asked on
 * that fresh state for the member. A DBAL transaction, as CrewLifecycle::whileLocked()
 * says why.
 *
 * @throws EntityNotFoundException the gift is gone, with its section or its crew
 * @throws \LogicException         the member may not take it over, the reason in French
 */
final class ClaimGift
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GiftRepository $giftRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(Gift $gift, User $member): void
    {
        $this->entityManager->getConnection()->transactional(function () use ($gift, $member): void {
            if (null === $this->giftRepository->findOneLocked($gift->getId())) {
                throw EntityNotFoundException::fromClassNameAndIdentifier(Gift::class, [(string) $gift->getId()]);
            }

            if (!$this->security->isGrantedForUser($member, 'gift.can_claim', $gift)) {
                $author = $gift->getCreatedBy();
                $reason = match (true) {
                    $author === $member => 'Ce cadeau est déjà le vôtre.',
                    null !== $author => \sprintf('%s a déjà repris ce cadeau.', $author->getPseudo()),
                    default => 'Vous ne pouvez pas reprendre ce cadeau.',
                };

                throw new \LogicException($reason);
            }

            $gift->setCreatedBy($member);
            $this->entityManager->flush();
        });
    }
}
