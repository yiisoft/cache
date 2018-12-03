<?php

return [
    \Psr\SimpleCache\CacheInterface::class => \yii\di\Reference::to('cache'),
    \yii\cache\CacheInterface::class => \yii\di\Reference::to('cache'),
    'cache' => [
        '__class' => \yii\cache\Cache::class,
    ],
];
