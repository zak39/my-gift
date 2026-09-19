<?php

namespace maizzle;

use Castor\Attribute\AsTask;

use function Castor\run;
use function Castor\context;
use function npm\install as npmInstall;

#[AsTask(description: 'Install the npm dependencies', aliases: ['n:m:i'])]
function installDependencies(): void
{
    run('cd maizzle');
    npmInstall();
}

#[AsTask(description: 'Build the Maizzle project', aliases: ['m:b'])]
function build(): void
{
    $context = context()->withWorkingDirectory('maizzle');
    npmInstall($context);
    run('npx maizzle build', $context);
}

#[AsTask(description: 'Run the Maizzle project in dev mode', aliases: ['m:d'])]
function dev(): void
{
    run('cd maizzle');
    npmInstall();
    run('npx maizzle serve');
}
