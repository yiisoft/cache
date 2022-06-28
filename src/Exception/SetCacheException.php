<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Exception;

use Yiisoft\Cache\Metadata\CacheItem;

final class SetCacheException extends CacheException
{
    private mixed $value;
    private CacheItem $item;

    public function __construct(string $key, mixed $value, CacheItem $item)
    {
        $this->value = $value;
        $this->item = $item;
        parent::__construct($key, 'Failed to store the value in the cache.');
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getItem(): CacheItem
    {
        return $this->item;
    }
}
