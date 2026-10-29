<?php

namespace server;

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

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
    #[AsArgument(description: 'prod or preprod')]
    string $env = 'prod',
): void {
    if (!\in_array($env, ['prod', 'preprod'], true)) {
        throw new \InvalidArgumentException("Unknown environment \"{$env}\": prod or preprod.");
    }

    run("docker compose -f compose.{$env}.yaml restart messenger-worker");

    io()->success("Messenger worker restarted on {$env}");
}
