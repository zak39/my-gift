<?php

namespace App\Services\Participation;

use App\Entity\Participant;
use App\Services\Lifecycle\GiftLifecycle;
use App\Strategy\ParticipationReleaseStrategy;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Releases a participation whose user deletes their account, through the one rule that
 * applies to it: the classes implementing ParticipationReleaseStrategy, next to this one.
 */
final class ReleaseParticipation
{
    /**
     * @param iterable<ParticipationReleaseStrategy> $strategies
     */
    public function __construct(
        #[AutowireIterator(ParticipationReleaseStrategy::class)]
        private readonly iterable $strategies,
        private readonly GiftLifecycle $giftLifecycle,
    ) {
    }

    public function __invoke(Participant $participation): void
    {
        $gift = $participation->getGift();

        if (null === $gift) {
            $this->strategyFor($participation)->release($participation);

            return;
        }

        // The rules tell the cases apart by the gift's status: chosen on the gift as
        // committed, under its lock, else a « prêt » or an « offert » meanwhile would make
        // the rule chosen refuse, and the whole account deletion fail.
        $this->giftLifecycle->whileLocked($gift, fn () => $this->strategyFor($participation)->release($participation));
    }

    private function strategyFor(Participant $participation): ParticipationReleaseStrategy
    {
        $applying = array_values(array_filter(
            iterator_to_array($this->strategies, false),
            fn (ParticipationReleaseStrategy $strategy) => $strategy->supports($participation),
        ));

        // The rules exclude each other: none would be a case forgotten, several an overlap
        // that the services' order would settle silently.
        if (1 !== \count($applying)) {
            throw new \LogicException(\sprintf('%d release rules apply to participation %d: exactly one should.', \count($applying), $participation->getId()));
        }

        return $applying[0];
    }
}
