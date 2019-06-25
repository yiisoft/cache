<?php

use Yiisoft\Factory\Definitions\Reference;

return [
    \Psr\SimpleCache\CacheInterface::class => Reference::to('cache'),
    \Yiisoft\Cache\CacheInterface::class => Reference::to('cache'),
    'cache' => [
        '__class' => \Yiisoft\Cache\Cache::class,
    ],
];
