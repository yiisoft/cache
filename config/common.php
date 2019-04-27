<?php

return [
    \Psr\SimpleCache\CacheInterface::class => \yii\di\Reference::to('cache'),
    \Yiisoft\Cache\CacheInterface::class => \yii\di\Reference::to('cache'),
    'cache' => [
        '__class' => \Yiisoft\Cache\Cache::class,
    ],
];
