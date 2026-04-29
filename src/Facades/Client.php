<?php

namespace G8Key\Client\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \G8Key\Client\Client
 */
class Client extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \G8Key\Client\Client::class;
    }
}
