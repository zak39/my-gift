<?php

namespace symfony;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\exit_code;
use function Castor\io;
use function Castor\run;
use function server\serverEnv;

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

/**
 * A Symfony command where the app runs, the same line on a dev machine and on a server:
 * `castor console app:user:search zak`. A server gives its variables to the containers only
 * (env_file of compose.*.yaml): a bin/console run on the server itself finds no DATABASE_URL,
 * and could not reach the "database" host anyway.
 * Castor answers its global options itself (-h/--help, -V, -q, -v…), wherever they are: `--`
 * hands them to the command, `castor console -- doctrine:migrations:migrate --help`.
 */
#[AsTask(description: 'Run a Symfony console command (in the php container on a server)', aliases: ['console'])]
function console(#[AsRawTokens] array $rawTokens): int
{
    $env = serverEnv();

    $command = null === $env
        ? ['symfony', 'console', ...$rawTokens]
        : ['docker', 'compose', '-f', "compose.{$env}.yaml", 'exec', 'php', 'bin/console', ...$rawTokens]
    ;

    // A terminal on all three sides, for the commands that ask a question. Otherwise no TTY
    // (Symfony Process refuses it, and it would ignore a 2> redirect) and no PTY (colours, \r);
    // Castor hands STDIN over in those two modes only, so piped data goes through withInput().
    $terminal = stream_isatty(\STDIN) && stream_isatty(\STDOUT) && stream_isatty(\STDERR);

    $context = context()
        ->withTty($terminal)
        ->withPty($terminal)
        ->withInput(stream_isatty(\STDIN) ? null : \STDIN)
    ;

    // exit_code(): a failing command answers with its own code, not with Castor's error block.
    return exit_code($command, context: $context);
}

#[AsTask(description: 'Consume async messengers and the scheduled tasks', aliases: ['sf:ca'])]
function consumeMessenger(): void
{
    run('symfony console messenger:consume async scheduler_default -vv');
}