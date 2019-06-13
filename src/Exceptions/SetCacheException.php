<?php

namespace Yiisoft\Cache\Exceptions;

use Throwable;
use Yiisoft\Cache\Cache;

class SetCacheException extends Exception
{
    /**
     * @var string
     */
    protected $key;

    protected $value;

    /**
     * @var Cache
     */
    protected $cache;

    public function __construct(
        string $key,
        $value,
        Cache $cache,
        $message = 'Could not store the value in the cache',
        $code = 0,
        Throwable $previous = null
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->cache = $cache;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return Cache
     */
    public function getCache(): Cache
    {
        return $this->cache;
    }
}
