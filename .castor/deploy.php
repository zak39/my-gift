<?php

namespace deploy;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

use function Castor\io;
use function Castor\run;

/**
 * Run on the server, after `git pull`: the code is mounted in the containers, so everything
 * built from it is rebuilt here. There is no .env in git: the containers read their
 * variables from .env.prod.local / .env.preprod.local, and `dump-env --empty` tells Symfony
 * not to look for a .env file.
 */
#[AsTask(description: 'Set up the pulled code on the server (env: prod or preprod)', aliases: ['deploy'])]
function deploy(
    #[AsArgument(description: 'prod or preprod')]
    string $env = 'prod',
): int {
    if (!\in_array($env, ['prod', 'preprod'], true)) {
        io()->error("Unknown environment \"{$env}\": prod or preprod.");

        return 1;
    }

    $compose = \sprintf('docker compose -f compose.%s.yaml', $env);

    $steps = [
        // --pull: the PHP and Caddy fixes of the base image, which a cached build never gets.
        'build --pull',
        'up -d',
        'exec php composer install --no-dev --no-scripts --classmap-authoritative --no-interaction',
        'exec php composer dump-env prod --empty',
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
        run("{$compose} {$step}");
    }

    io()->success("Deployed to {$env}");

    return 0;
}
