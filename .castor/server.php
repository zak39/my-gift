<?php

namespace server;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\io;
use function Castor\run;
use function composer\install;
use function database\fixtures;
use function database\migrate;
use function docker\down;
use function docker\up;
use function symfony\start as sfStart;
use function symfony\stop as sfStop;

#[AsTask(
    description: 'Start the web server and all docker services. It can use as dev environment.',
    aliases: ['start'])]
function start(bool $quietly = false, bool $detach = false, bool $firstInstall = false): void
{
    if ($firstInstall) {
        install($quietly);
    }

    up($quietly, services: ['database']);
    migrate($quietly);
    fixtures($quietly);
    sfStart($detach);
}

#[AsTask(description: 'Start the web server and all docker services', aliases: ['start:demo'])]
function startDemo(bool $quietly = false, bool $firstInstall = false): void
{
    if ($firstInstall) {
        install($quietly);
    }

    up($quietly);
    migrate($quietly);
    fixtures($quietly);
}

#[AsTask(description: 'Stop the web server and all docker services', aliases: ['stop'])]
function stop(bool $quietly = false, bool $purge = false): void
{
    down($purge, $quietly);
    sfStop();
}

#[AsTask(description: 'Restart the Messenger worker on the server (env: prod or preprod)', aliases: ['restart:messenger'])]
function restartMessenger(
    #[AsArgument(description: 'prod or preprod: this server\'s own by default')]
    ?string $env = null,
): void {
    $env = resolveServerEnv($env);

    run("docker compose -f compose.{$env}.yaml restart messenger-worker");

    io()->success("Messenger worker restarted on {$env}");
}

/**
 * The server environment this checkout runs, or null on a dev machine. A server is told by
 * the file its containers cannot start without (env_file of compose.*.yaml): nothing to set
 * in a shell, so it holds in a cron, under sudo or over ssh, and APP_ENV stays Symfony's.
 *
 * @return 'prod'|'preprod'|null
 */
function serverEnv(): ?string
{
    $dir = context()->workingDirectory;

    $envs = array_values(array_filter(
        ['prod', 'preprod'],
        static fn (string $env): bool => is_file("{$dir}/.env.{$env}.local"),
    ));

    // Guessing would be worse: a command meant for the preprod could run on the prod.
    if (\count($envs) > 1) {
        throw new \RuntimeException('Both .env.prod.local and .env.preprod.local are here: keep only the one of this server.');
    }

    return $envs[0] ?? null;
}

/**
 * The environment a server task acts on: this server's own, and the one asked only if it is
 * that one. `castor deploy` on the preprod server would otherwise check out a prod tag over
 * the code its containers run, before compose stops on the missing .env.prod.local.
 *
 * @return 'prod'|'preprod'
 */
function resolveServerEnv(?string $env): string
{
    $serverEnv = serverEnv()
        ?? throw new \RuntimeException('Neither .env.prod.local nor .env.preprod.local is here: this task runs on a server.')
    ;

    if (null !== $env && $env !== $serverEnv) {
        throw new \RuntimeException("This server runs {$serverEnv}, not \"{$env}\".");
    }

    return $serverEnv;
}
