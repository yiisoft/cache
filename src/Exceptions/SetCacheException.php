<?php

namespace Yiisoft\Cache\Exceptions;

use Throwable;
use Yiisoft\Cache\CacheInterface;

class SetCacheException extends Exception
{
    /**
     * @var string
     */
    protected $key;

    protected $value;

    /**
     * @var CacheInterface
     */
    protected $cache;

    public function __construct(
        string $key,
        $value,
        CacheInterface $cache,
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
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }
}
