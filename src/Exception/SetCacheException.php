<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Exception;

use Yiisoft\Cache\CacheInterface;

final class SetCacheException extends CacheException
{
    /** @var string $key */
    private string $key;
    /** @var mixed $value */
    private $value;

    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * SetCacheException constructor.
     * @param string $key
     * @param mixed $value
     * @param CacheInterface $cache
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $key,
        $value,
        CacheInterface $cache,
        string $message = 'Could not store the value in the cache',
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->cache = $cache;
        parent::__construct($message, $code, $previous);
    }

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

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }
}
