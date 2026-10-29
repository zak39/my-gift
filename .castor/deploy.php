<?php

namespace deploy;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

use function Castor\capture;
use function Castor\context;
use function Castor\io;
use function Castor\run;

/**
 * Run on the server: a release is a git tag and nothing else (#101). The tag is checked out,
 * then everything built from the code is rebuilt here, since the code is mounted in the
 * containers. There is no .env in git: the containers read their variables from
 * .env.prod.local / .env.preprod.local, and `dump-env --empty` tells Symfony not to look for
 * a .env file. The tag is the app's version (APP_VERSION, compose.*.yaml).
 */
#[AsTask(description: 'Deploy a tag on the server (env: prod or preprod)', aliases: ['deploy'])]
function deploy(
    #[AsArgument(description: 'prod or preprod')]
    string $env = 'prod',
    #[AsArgument(description: 'The tag to deploy, to roll back for instance; the latest one by default')]
    ?string $tag = null,
): int {
    if (!\in_array($env, ['prod', 'preprod'], true)) {
        io()->error("Unknown environment \"{$env}\": prod or preprod.");

        return 1;
    }

    run('git fetch --tags');

    $tags = deployableTags($env);
    $tag ??= $tags[0] ?? null;

    if (null === $tag) {
        io()->error("No tag to deploy on {$env}.");

        return 1;
    }

    // Also what makes $tag safe in the shell commands below: it is one of git's own tags.
    if (!\in_array($tag, $tags, true)) {
        io()->error("\"{$tag}\" is not a tag {$env} can deploy.");

        return 1;
    }

    run("git checkout --detach {$tag}");

    $compose = \sprintf('docker compose -f compose.%s.yaml', $env);
    // Read by compose when it creates the php container (up -d); a restart keeps the old value.
    $context = context()->withEnvironment(['APP_VERSION' => $tag]);

    $steps = [
        // --pull: the PHP and Caddy fixes of the base image, which a cached build never gets.
        'build --pull',
        'up -d',
        'exec php composer install --no-dev --no-scripts --classmap-authoritative --no-interaction',
        "exec php composer dump-env {$env} --empty",
        // cache:clear, assets:install, importmap:install
        'exec php composer run-script --no-dev post-install-cmd',
        'exec php bin/console tailwind:build --minify',
        'exec php bin/console asset-map:compile',
        'exec php bin/console doctrine:migrations:migrate -n',
        // A long-running process: it would keep the old code.
        'restart messenger-worker',
    ];

    // run() throws on the first step that fails, which stops the deployment there.
    foreach ($steps as $step) {
        run("{$compose} {$step}", context: $context);
    }

    io()->success("Deployed {$tag} to {$env}");

    return 0;
}

/**
 * The tags $env may deploy, the latest first: final versions only on prod (1.0.0), release
 * candidates too on preprod (1.1.0-rc1). Tags carry no "v".
 *
 * @return list<string>
 */
function deployableTags(string $env): array
{
    $pattern = match ($env) {
        // Only final versions: 1.0.0, 1.1.0…
        'prod' => '/^\d+\.\d+\.\d+$/',
        // Release candidates too: 1.1.0-rc1…
        'preprod' => '/^\d+\.\d+\.\d+(-rc\d+)?$/',
    };

    // Latest first; versionsort.suffix puts 1.1.0-rc1 before 1.1.0, as SemVer does (without
    // it, git sorts the rc after). The pattern also drops the tags that are no release.
    $tags = \explode("\n", capture('git -c versionsort.suffix=-rc tag --list --sort=-v:refname'));

    return \array_values(\array_filter($tags, static fn ($tag) => \preg_match($pattern, $tag)));
}
