<?php

use Castor\Attribute\AsContext;
use Castor\Context;

#[AsContext(name: 'dev', default: true)]
function dev_context(): Context
{
    return new Context(environment: ['APP_ENV' => 'dev']);
}

#[AsContext(name: 'prod')]
function prod_context(): Context
{
    return new Context(environment: ['APP_ENV' => 'prod']);
}
