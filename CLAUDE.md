# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

my-gift is a Symfony 7.4 (PHP 8.4+) web app for collaborative gift lists: users create
`Section`s containing `Gift`s, and other `Participant`s reserve a gift or pool contributions
toward it. Learning project based on an OpenClassrooms Symfony 7 course.

## Development commands

This project uses [Castor](https://castor.jolicode.com/) (`castor.php` + `.castor/*.php`) as
the task runner, wrapping Symfony CLI, Docker Compose and Composer. Run `castor` with no
arguments to list all tasks.

- `castor start` (alias of `server:start`) — brings up the `database` Docker service,
  runs migrations + fixtures, and starts the Symfony local web server. Add `--first-install`
  on a fresh clone to run `composer install` first.
- `castor stop` — stops the Symfony server and Docker services.
- `castor db:m:m` — run Doctrine migrations (`doctrine:migrations:migrate -n`).
- `castor db:f` — load fixtures (`doctrine:fixtures:load -n`).
- `castor db:drop` — drop the database.
- `castor sf:tw:build --watch` — run the Tailwind CSS watcher (needed for style changes,
  since the frontend build uses `symfonycasts/tailwind-bundle` + AssetMapper, not Node/webpack).
- `castor sf:b:f` (`buildFront`) — build Tailwind and compile assets via `asset-map:compile`.
- `castor c:i` / `castor c:c` — `composer install` / remove `vendor` + `composer.lock`.
- `castor d:u` / `castor d:d` / `castor d:status` — Docker Compose up/down/status.

Direct Symfony/PHP commands (via `symfony console ...` or `php bin/console ...`):
- `php-cs-fixer fix --dry-run` (composer script `cs:check`) — check code style (`@Symfony` ruleset).
- `composer cs:fix` — apply code style fixes.
- `vendor/bin/phpunit` or `symfony console` + `php bin/phpunit` — run the test suite
  (config in `phpunit.dist.xml`; test classes live under `tests/`). Run a single test with
  `vendor/bin/phpunit --filter testMethodName path/to/TestFile.php`.
- `symfony console doctrine:migrations:diff` / `doctrine:migrations:migrate` — after changing
  an entity, generate and run a migration (migrations live in `migrations/`).

### Email templates (Maizzle)

Transactional email HTML is authored separately in `maizzle/` (Vue-based Maizzle project,
its own `package.json`/npm toolchain), then built to static HTML consumed by the app's mailer.
- `castor m:b` (`build`) — build the Maizzle project.
- `castor m:d` (`dev`) — run Maizzle in watch/serve mode.
- `castor n:m:i` — install Maizzle's npm dependencies.

## Architecture

### Domain model and gift lifecycle

Core entities (`src/Entity`): `User`, `Section` (owned by a user, groups gifts),
`Gift` (belongs to a section, has a `GiftStatus`), `Participant` (links a `User` to a `Gift`
with a `ParticipantStatus` and, for contributions, an amount).

A `Crew` (members as `CrewMember` rows, all with the same rights; its owner `Crew::$owner`
alone deletes it, and leaves it only once passed on with `CrewLifecycle::transfer()`;
`Crew::$createdBy` is history only, null once its user is deleted) shares a gift list: a
`Section` with a crew belongs to the crew's list, not to its creator's. `crew.can_transfer`
/ `Crew::getOwner()` is that one role, not `gift.is_owner`. Ownership is
`$gift->getOwnership()->includes($user)` / `Section::isOwnedBy()` (crew members, or else the
creator), never a bare `getCreatedBy()` comparison. A crew's gift or section keeps no author
(`createdBy` null) once its user deleted their account (`UserLifecycle` empties it; required
out of a crew by `App\Validator\NotNullOutOfCrew`): read it null-safe, and name the crew rather than the
author to its givers (`App\Services\Gift\GiftOwnerLabel`). Released by a section or crew
deleted (`App\Services\Section\ReleaseGifts`), such a gift goes with it, its givers told,
unless a member took it over first (`gift.can_claim`, `App\Services\Gift\ClaimGift`).

`Gift` is composed of value objects, one per concern: `getOwnership()` (`App\ValueObject\GiftOwnership`:
`includes()`) and `getPool()` (`App\ValueObject\GiftPool`: what was collected, what
remains, rounding and overshoot rules) are value objects built on demand, not persisted. In
Twig, `gift.pool.totalContributed`, `gift.pool.complete`… — the Maizzle email sources use the
same paths. No `GiftCrew`/`GiftPersonal` entity classes or Doctrine inheritance: a gift
enters a crew or opens a pool over its life, and a Doctrine entity cannot change class. That
is a reason about this entity, not a rule against splitting classes by role elsewhere: the
yardstick is the most readable code (clean code, SOLID). Invitations (`CrewInvitation`) run through the `crew_invitation`
workflow and `src/Services/Lifecycle/CrewLifecycle.php`, as friend requests do through
`FriendRequestLifecycle`.

`Gift` status is driven by a Symfony Workflow **state machine** (`config/packages/workflow.yaml`,
workflow name `gift_request`), with places/transitions defined via the `GiftStatus` enum
(`CREATED → RESERVED|CONTRIBUTING → READY → GIVEN → ARCHIVED`, plus `disengage`/
`cancelled_contribution` transitions back to `CREATED`). All transition logic and business
rules (checking `$workflow->can()`, applying transitions, recomputing whether contributions
cover the price) are centralized in `src/Services/Lifecycle/GiftLifecycle.php` — controllers should call
into this service rather than mutating `Gift`/`Participant` status directly.

Domain events (`src/Event`) are dispatched for cross-cutting side effects and handled by
listeners in `src/EventListener` using `#[AsEventListener]` (e.g. generating a default Dicebear
avatar when a `User` is created). Authorization for gift/section ownership is implemented as
Symfony Security Voters in `src/Security/Voter` (e.g. `gift.is_owner` attribute).

### Twig components (UX)

UI is built with Symfony UX Twig Components (`symfony/ux-twig-component`), not raw includes.
Each component is a PHP class under `src/Twig/Components/...` with `#[AsTwigComponent]`,
paired with a template at the mirrored path under `templates/components/...`
(e.g. `src/Twig/Components/Molecule/Breadcrumbs.php` ↔
`templates/components/Molecule/Breadcrumbs.html.twig`). Components are organized loosely by
atomic-design tiers (`Atomic/`, `Molecule/`, `Organism/`), with feature groupings inside a
tier where several components share one (`Molecule/Gift/`, `Molecule/Navbar/`…); a few
older ones (`Alert`, `CardGift`, `Modal`) still sit at the root. When adding
a new component, follow this class+template pairing.

Live/reactive UI where needed uses `symfony/ux-live-component`; page transitions use
`symfony/ux-turbo`. Client-side behavior beyond that is implemented as Stimulus controllers in
`assets/controllers/` (registered in `assets/controllers.json`).

### Frontend build

No Node/webpack build for the main app: JS/CSS are served via Symfony **AssetMapper**
(`importmap.php`, `config/packages/asset_mapper.yaml`). CSS uses Tailwind v4 via
`symfonycasts/tailwind-bundle` (entry at `assets/styles/app.css`) plus daisyUI
(`assets/vendor/daisyui`) — edit `assets/styles/app.css` / Tailwind config and rebuild with
`castor sf:tw:build`, don't hand-edit compiled output. The separate `maizzle/` project (see
above) has its own independent Node toolchain for email HTML only.

### File uploads

`vich/uploader-bundle` handles file uploads on `Gift` and `User` (avatars), with custom
directory namers in `src/Vich/`. A gift's image is filed with the list it belongs to
(`GiftDirectoryNamer::directoryFor()`): `crews/{id}/images/gifts/` for a crew gift,
`users/{author}/images/gifts/` otherwise. Vich resolves the folder on every read, so
`MoveGiftImageListener` moves the file whenever a gift enters or leaves a crew, and removes
a deleted crew's folder: copied on flush, the original removed once the database agreed
(`App\Services\PendingFileRemovals`; whoever opens a wider transaction, like
`UserLifecycle`, calls `removeAll()` after its commit and `forget()` on a rollback). Every upload is stored as WebP: `ConvertUploadedImageToWebpListener`
(`vich_uploader.pre_upload`) rewrites the file in place through `App\Services\WebpConverter`
(Imagick, `ext-imagick`), capped per mapping (`App\Vich\UploadedImage`, which also holds the accepted formats and pixel limit, and checks a file with `accepts()`), before
`SmartUniqueNamer` names it from its content. `App\Services\DicebearAvatarGenerator` generates default
avatars; `App\Services\ProductPage\ProductPageReader` reads a pasted product URL to prefill
gift fields (title, description, image, price) with the first of its sources the page supports
(`ProductPage/Source/`: Open Graph, else JSON-LD `schema.org/Product`, Twitter Card, `<title>`),
and `App\Services\ImageDownloader` fetches its image.

### Routing

Routes are defined with `#[Route]` PHP attributes directly on controller methods
(`src/Controller/`), not YAML route files.

Controllers are filed by the first URL segment, which says whose resource a page is
about; the class carries that prefix (`#[Route('/me/...')]`), and the segment tells which
voter guards it:

- `Me/` — `/me/...`, the logged-in user's own space (dashboard, list, profile, friends,
  favorites, crews, received invitations). No user id in the URL; nested resource ids
  still get their voter (`section.is_owner`, `gift.is_owner`…), and a crew's section or
  gift is not found there
- `User/` — `/users/{id}/...`, another user: their list (`user.can_see_gifts`, a 404 for
  anyone but them and their friends: the ids follow each other), unfriend / block. No page
  lists the users, and nobody is asked as a friend by id
- `Invitation/` — `/invitation/{slug}`, a user's invitation link, the only way to ask
  someone as a friend: public (its Open Graph card, `LinkPreviewBuilder::forInvitation()`),
  the request a POST. The slug lives on `FriendBook`, random (`InvitationSlugGenerator`) or
  chosen; changing it ends the old link (404). A newcomer keeps it through the registration
  and the signed confirmation link (`?invitation=`), then the login leads back to it
- `Crew/` — `/crews/{id}/...`, one crew run by its members (`crew.is_member`); a nested
  section or gift must belong to that crew
- `Gift/` — `/gift/{id}/...`, a gift from the giver's side: its page, favorites,
  reservation / contribution / completion requests. Its owners see the same page from
  their own URL (`/me/gifts/{id}/show`, `/crews/{id}/gifts/{gift}/show`); one opening
  `/gift/{id}/show` is redirected there. A route `condition` cannot tell them apart: it
  runs before the firewall knows the user, so each point of view gets its own path
- `Security/` — login, registration, password reset; `Admin/` — EasyAdmin

A nested resource is loaded through the list of its URL, never checked after loading:
`#[MapEntity(expr: 'repository.findOneInCrew(gift, id)')]`, `repository.findOnePersonal(id)`,
`repository.findOneBy({"id": section, "crew": id})`. Not found in that list means 404, before
any `#[IsGranted]` runs, so an outsider cannot tell the id exists. Under `/me`, the owner
voter of a gift or a section answers 404 too (`#[IsGranted('gift.is_owner', ..., statusCode:
404)]`): someone else's gift or section does not exist in the user's own space. Invitations
and friend requests are not concerned: their POST actions check a CSRF token, then the
workflow guards, and answer with a flash. Nor is an optional query parameter, which is no
nested resource: `/me/gifts/new?section=` ignores a section that is not the user's own (the
gift is created unfiled) instead of answering 404.

A POST action checks its CSRF token with `#[IsCsrfTokenValid]` (an `Expression` on `args[...]`
for an id built from an entity or route argument, the same id as the template's `csrf_token()`),
not by hand. Symfony runs it before `#[IsGranted]` and answers a refused token by redirecting to
the login page; `App\EventListener\RefuseInvalidCsrfTokenListener` decorates its listener to run it
after `#[IsGranted]` (so the `/me` 404 still comes first) and to answer 403, app-wide.

The same resource may have a controller per folder (`Me/SectionController`,
`User/SectionController`, `Crew/SectionController`), one per point of view. What two of
them do alike lives in a service, one class per action with a single `__invoke()`
(`Services/Gift/CreateGift`, `UpdateGift`, `DeleteGift`; `Services/Section/CreateSection`,
`DeleteSection`), a form's choices live in its FormType (form types are services), and URLs
come from `src/Services/Url/`, one generator per point of view, the caller picking the one
it already knows it is: `GiftOwnerUrlGenerator` (a gift's pages for its owners, in its
crew's list or its author's own), `SectionUrlGenerator`, `ViewerUrlGenerator` (a giver's:
`/gift/{id}/show`, the list the gift is part of). Most methods are also Twig functions
(`gift_owner_show_url()`, `gift_edit_url()`, `section_edit_url()`…); only a component
shared by owners' and givers' pages (`Molecule:Gift:ShowButton`) chooses, with
`is_granted('gift.is_owner')`. The "Partager" buttons copy links from `ShareUrlGenerator`
(`gift_share_url()`, `crew_share_url()`, signed for 6 months for whoever may see what they
show; `own_invitation_url()`, a user's own list as they share it, public): a visitor who is not logged in, a messenger's preview
bot, gets on a signed link the login page carrying its Open Graph card (`SharedLinkPreviewListener`,
`LinkPreviewBuilder`); any other page carries the app's own card (`Molecule:LinkPreview`).
The paths linked from
emails (`/users/{id}/sections`, `/crews/{id}`, `/gift/{id}/show`, `/me/crews`,
`/me/sections`) must not move.

<easyadmin-guidelines>
This project uses EasyAdmin 5.6.1.

Before creating or modifying admin dashboards, CRUD controllers, fields, actions,
filters or their tests, read and follow the `easyadmin` skill at `.claude/skills/easyadmin/SKILL.md`.

Prefer the makers (`make:admin:dashboard`, `make:admin:crud`) to generate the
initial classes instead of writing them from scratch.

Team conventions for EasyAdmin, if any, are in the `## EasyAdmin conventions`
section of this file, outside this block.
</easyadmin-guidelines>
