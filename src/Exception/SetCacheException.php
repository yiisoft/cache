<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Exception;

use Yiisoft\Cache\Metadata\CacheItem;

final class SetCacheException extends CacheException
{
    /**
     * @var mixed
     */
    private $value;
    private CacheItem $item;

    /**
     * @param string $key
     * @param mixed $value
     * @param CacheItem $item
     */
    public function __construct(string $key, $value, CacheItem $item)
    {
        $this->value = $value;
        $this->item = $item;
        parent::__construct($key, 'Failed to store the value in the cache.');
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getItem(): CacheItem
    {
        return $this->item;
    }
}
