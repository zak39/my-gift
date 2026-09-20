# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

my-gift is a Symfony 7.4 (PHP 8.2+) web app for collaborative gift lists: users create
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

`Gift` status is driven by a Symfony Workflow **state machine** (`config/packages/workflow.yaml`,
workflow name `gift_request`), with places/transitions defined via the `GiftStatus` enum
(`CREATED → RESERVED|CONTRIBUTING → READY → GIVEN → ARCHIVED`, plus `disengage`/
`cancelled_contribution` transitions back to `CREATED`). All transition logic and business
rules (checking `$workflow->can()`, applying transitions, recomputing whether contributions
cover the price) are centralized in `src/Services/GiftLifecycle.php` — controllers should call
into this service rather than mutating `Gift`/`Participant` status directly.

Domain events (`src/Event`) are dispatched for cross-cutting side effects and handled by
listeners in `src/EventListener` using `#[AsEventListener]` (e.g. generating a default Dicebear
avatar when a `User` is created). Authorization for gift/section ownership is implemented as
Symfony Security Voters in `src/Security/Voter` (e.g. `gift.is_creator` attribute).

### Twig components (UX)

UI is built with Symfony UX Twig Components (`symfony/ux-twig-component`), not raw includes.
Each component is a PHP class under `src/Twig/Components/...` with `#[AsTwigComponent]`,
paired with a template at the mirrored path under `templates/components/...`
(e.g. `src/Twig/Components/Molecule/Breadcrumbs.php` ↔
`templates/components/Molecule/Breadcrumbs.html.twig`). Components are organized loosely by
atomic-design tiers (`Molecule/`, `Organism/`) plus feature groupings (`Gift/`, `Dashboard/`,
`Navbar/`, `Router/`). When adding a new component, follow this class+template pairing.

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
directory namers in `src/Vich/`. `App\Services\DicebearAvatarGenerator` generates default
avatars; `App\Services\ImageDownloader` + `App\Services\OpenGraphApi` fetch Open Graph metadata
to prefill gift fields from a pasted product URL.

### Routing

Routes are defined with `#[Route]` PHP attributes directly on controller methods
(`src/Controller/`), not YAML route files.
