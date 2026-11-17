<?php

namespace App\Services\Friend;

use App\Entity\FriendBook;
use App\Repository\FriendBookRepository;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * The book behind an invitation link, for its page, which would otherwise tell a slug in use
 * from a free one (the page or a 404). A caller who missed too many links in the hour is
 * refused any, found or not: a slug chosen by its owner may be a word (famille-martin), and
 * only its rarity protects it.
 */
final class FindInvitation
{
    public function __construct(
        private readonly FriendBookRepository $friendBookRepository,
        private readonly RateLimiterFactoryInterface $invitationLookupLimiter,
    ) {
    }

    /**
     * @throws TooManyRequestsHttpException once the caller's misses are used up
     */
    public function __invoke(string $slug, ?string $clientIp): ?FriendBook
    {
        // An IPv6 caller by its /64, the block one connection gets: otherwise each of its
        // addresses would start afresh. An IPv4 one whole.
        $limiter = $this->invitationLookupLimiter->create(null === $clientIp ? 'unknown' : IpUtils::anonymize($clientIp, 0, 8));

        // No token taken: a link found costs nothing, but none is looked up once spent.
        $misses = $limiter->consume(0);
        if (0 === $misses->getRemainingTokens()) {
            throw new TooManyRequestsHttpException($misses->getRetryAfter()->getTimestamp() - time());
        }

        $book = $this->friendBookRepository->findOneBySlug($slug);

        if (null === $book) {
            $limiter->consume();
        }

        return $book;
    }
}
