<?php

namespace npm;

use Castor\Attribute\AsTask;
use Castor\Context;

use function Castor\run;

#[AsTask(description: 'Install the npm dependencies', aliases: ['npm:i'])]
function install(?Context $context = null): void
{
    run('npm install', $context);
}