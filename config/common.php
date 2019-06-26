<?php

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Factory\Definitions\Reference;

return [
    PsrCacheInterface::class => Reference::to('cache'),
    CacheInterface::class => Reference::to('cache'),
    'cache' => [
        '__class' => Cache::class,
    ],
];
