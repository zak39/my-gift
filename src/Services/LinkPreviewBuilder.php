<?php

namespace App\Services;

use App\Entity\Crew;
use App\Entity\FriendBook;
use App\Entity\Gift;
use App\Entity\User;
use App\Repository\CrewRepository;
use App\Repository\GiftRepository;
use App\Services\Dto\LinkPreviewDto;
use App\Services\Dto\LinkPreviewImageDto;
use App\Services\Url\AvatarUrlGenerator;
use App\Services\Url\ShareUrlGenerator;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

use function Symfony\Component\String\u;

/**
 * The card of a link copied with "Partager" (ShareUrlGenerator), for whoever opens it
 * without being logged in: a link-preview bot, or a friend about to log in. One method per
 * page that can be shared, as BreadcrumbBuilder has, an invitation link's, and the app's
 * own card for any other.
 */
final class LinkPreviewBuilder
{
    private const APP_TAGLINE = 'My Gift, des cadeaux qui ne font jamais doublon';

    private const GIFT_ROUTE = 'app_gift_show';
    private const CREW_ROUTE = 'app_crew_show';

    public function __construct(
        private readonly UriSigner $uriSigner,
        private readonly GiftRepository $giftRepository,
        private readonly CrewRepository $crewRepository,
        private readonly UploaderHelper $uploaderHelper,
        private readonly UrlHelper $urlHelper,
        private readonly Packages $packages,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AvatarUrlGenerator $avatarUrlGenerator,
        private readonly ShareUrlGenerator $shareUrlGenerator,
    ) {
    }

    /**
     * The card of any page but a shared link: its name, tagline and default picture.
     */
    public function forApp(): LinkPreviewDto
    {
        return new LinkPreviewDto(
            self::APP_TAGLINE,
            'Créez une section par occasion, partagez le lien. Vos proches réservent un cadeau ou cagnottent ensemble, sans jamais se marcher dessus.',
            $this->urlGenerator->generate('app_landing_page', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->defaultImage(),
        );
    }

    /**
     * The card of an invitation link (Invitation\InvitationController), public as the page:
     * the owner's pseudo and avatar only, what anyone holding the link sees there. Neither
     * their full name, for their friends only (user.can_see_fullname), nor their gifts.
     */
    public function forInvitation(FriendBook $book): LinkPreviewDto
    {
        $owner = $book->getOwner();
        $pseudo = $owner->getPseudo();

        return new LinkPreviewDto(
            "$pseudo vous invite sur My Gift",
            "Devenez ami·es : vous verrez les sections de cadeaux de $pseudo, et $pseudo verra les vôtres.",
            $this->shareUrlGenerator->invitation($book->getInvitationSlug()),
            $this->image($this->avatarUrlGenerator->url($owner), "Photo de profil $pseudo"),
        );
    }

    /**
     * One of the pages "Partager" links to (ShareUrlGenerator), whose card this builder makes.
     */
    public function isSharedPage(Request $request): bool
    {
        return \in_array($request->attributes->get('_route'), [self::GIFT_ROUTE, self::CREW_ROUTE], true);
    }

    /**
     * Null when the link was not signed, or no longer shows anything to friends: the visitor
     * then gets the app's own card, as for any other page.
     */
    public function forSharedLink(Request $request): ?LinkPreviewDto
    {
        if (!$this->uriSigner->checkRequest($request)) {
            return null;
        }

        $id = $request->attributes->getInt('id');
        $url = $request->getUri();

        return match ($request->attributes->get('_route')) {
            self::GIFT_ROUTE => $this->gift($this->giftRepository->find($id), $url),
            self::CREW_ROUTE => $this->crew($this->crewRepository->find($id), $url),
            default => null,
        };
    }

    /**
     * Only a gift its owner's friends see, as GiftVisibilityVoter decides for them: a link
     * signed before the gift went back to the drafts shows nothing more.
     */
    private function gift(?Gift $gift, string $url): ?LinkPreviewDto
    {
        if (null === $gift || !$gift->isSharedWithFriends()) {
            return null;
        }

        $crew = $gift->getCrew();
        $description = null !== $crew
            ? "Une idée cadeau du groupe « {$crew->getName()} »"
            : "Une idée cadeau de {$gift->getCreatedBy()->getPseudo()}"
        ;

        if (null !== $gift->getPrice()) {
            // The request's locale, as Twig's format_currency reads it on the pages.
            $price = (new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::CURRENCY))->formatCurrency($gift->getPrice(), 'EUR');
            $description .= " · $price";
        }

        if (null !== $gift->getDescription() && '' !== trim($gift->getDescription())) {
            $description .= ' — '.u($gift->getDescription())->collapseWhitespace()->truncate(150, '…', false);
        }

        return new LinkPreviewDto(
            $gift->getTitle(),
            $description,
            $url,
            $this->image($this->uploaderHelper->asset($gift, 'imageFile'), $gift->getTitle()),
        );
    }

    /**
     * No picture of its own: the app's default one, rather than the avatar of one member.
     */
    private function crew(?Crew $crew, string $url): ?LinkPreviewDto
    {
        if (null === $crew) {
            return null;
        }

        $pseudos = array_map(static fn (User $member) => $member->getPseudo(), $crew->getMembers());
        $last = array_pop($pseudos);
        $where = 'dans les sections communes';

        if (null !== $last) {
            $where .= ' de '.([] === $pseudos ? $last : implode(', ', $pseudos)." et $last");
        }

        return new LinkPreviewDto(
            $crew->getName(),
            $this->giftCount($crew->getGiftCount(), $where),
            $url,
            $this->defaultImage(),
        );
    }

    private function giftCount(int $count, string $where): string
    {
        return match ($count) {
            0 => "Pas encore d'idée cadeau $where",
            1 => "1 idée cadeau $where",
            default => "$count idées cadeaux $where",
        };
    }

    /**
     * The uploaded picture, or the app's default one when there is none.
     */
    private function image(?string $path, string $alt): LinkPreviewImageDto
    {
        return null !== $path
            ? new LinkPreviewImageDto($this->urlHelper->getAbsoluteUrl($path), $alt)
            : $this->defaultImage()
        ;
    }

    /**
     * About 1200×630, the size messengers want.
     */
    private function defaultImage(): LinkPreviewImageDto
    {
        return new LinkPreviewImageDto(
            $this->urlHelper->getAbsoluteUrl($this->packages->getUrl('images/link-preview.png')),
            self::APP_TAGLINE,
            'image/png',
            1200,
            630,
        );
    }
}
