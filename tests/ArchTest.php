<?php

arch('it does not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->each->not->toBeUsed();

arch('verifier has no HTTP dependencies')
    ->expect('G8Key\\Client\\Services\\Verifier')
    ->not->toUse([
        'Illuminate\\Http\\Client\\Factory',
        'GuzzleHttp\\Client',
    ]);

arch('exceptions extend the package base exception')
    ->expect('G8Key\\Client\\Exceptions')
    ->classes()
    ->toExtend('G8Key\\Client\\Exceptions\\G8KeyClientException');

arch('contracts are interfaces')
    ->expect('G8Key\\Client\\Contracts')
    ->toBeInterfaces();

arch('console commands extend Laravel Command')
    ->expect('G8Key\\Client\\Console')
    ->toExtend('Illuminate\\Console\\Command');

arch('middleware lives under Http')
    ->expect('G8Key\\Client\\Http\\Middleware')
    ->toUse('Illuminate\\Http\\Request');
