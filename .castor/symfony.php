<?php

namespace symfony;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use function Castor\io;
use function Castor\run;

#[AsTask(description: 'echo')]
function hello(): void
{
    io()->text('Hello Symfony');
}

#[AsTask(description: 'Run the server in background', aliases: ['sf:start'])]
function start(#[AsOption(name: 'detach', description: 'Run the Symfony local web server in the background')] bool $detach = false): void
{
    $cmd = ['symfony', 'server:start'];
    
    if ($detach) {
        $cmd[] = '-d';
    }

    $result = run($cmd);

    if(!$result->isSuccessful()) {
        io()->error('Impossible to run the server');
        return;
    }
}

#[AsTask(description: 'Run the server in background', aliases: ['sf:stop'])]
function stop(): void
{
    $result = run('symfony server:stop');

    if(!$result->isSuccessful()) {
        io()->error('Impossible to stop the server');
        return;
    }

    io()->success('The server is stopped');
}

#[AsTask(description: 'Know the server status', aliases: ['sf:status'])]
function status(): void
{
    run('symfony server:status');
}

#[AsTask(description: 'Run the TailwindCSS watcher', aliases: ['sf:tw:build'])]
function tailwindBuild(#[AsOption(name: 'watch', description: 'Watch for changes')] bool $watch = false): void
{
    $cmd = ['symfony', 'console', 'tailwind:build'];

    if ($watch) {
        $cmd[] = '--watch';
    }

    run($cmd);
}

#[AsTask(description: 'Build TailwindCSS and compile via asset-map', aliases: ['sf:b:f'])]
function buildFront(): void
{
    tailwindBuild();
    run('symfony console asset-map:compile');
}

#[AsTask(description: 'Consume async messengers and the scheduled tasks', aliases: ['sf:ca'])]
function consumeMessenger(): void
{
    run('symfony console messenger:consume async scheduler_default -vv');
}