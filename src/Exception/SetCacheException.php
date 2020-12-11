<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Exception;

final class SetCacheException extends CacheException
{
    private $value;
    private ?int $ttl;

    public function __construct(string $key, $value, ?int $ttl)
    {
        $this->value = $value;
        $this->ttl = $ttl;
        parent::__construct($key, 'Failed to store the value in the cache.');
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }
}
