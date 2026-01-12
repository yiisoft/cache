<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface as YiisoftCacheInterface;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

return [
    YiisoftCacheInterface::class => Cache::class,
    PsrCacheInterface::class => ArrayCache::class,
];
