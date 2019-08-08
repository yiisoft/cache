<?php

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Exception;
use Psr\SimpleCache\CacheInterface;

/**
 * Class BaseCache is a base class for different cache backend implementations, contains common functionality
 * @package Yiisoft\Cache
 */
abstract class BaseCache implements CacheInterface
{
    public const EXPIRATION_INFINITY = 0;
    /**
     * @var int|null default TTL for a cache entry. null meaning infinity, negative or zero results in cache key deletion.
     * This value is used by {@see set()} and {@see setMultiple()}, if the duration is not explicitly given.
     */
    private $defaultTtl;

    /**
     * @return int|null
     */
    public function getDefaultTtl(): ?int
    {
        return $this->defaultTtl;
    }

    /**
     * @param int|DateInterval|null $defaultTtl
     */
    public function setDefaultTtl($defaultTtl): void
    {
        $this->defaultTtl = $this->normalizeTtl($defaultTtl);
    }

    /**
     * @param $ttl
     * @return int
     */
    protected function ttlToExpiration($ttl): int
    {
        $ttl = $this->normalizeTtl($ttl);

        if ($ttl === null) {
            $expiration = static::EXPIRATION_INFINITY;
        } elseif ($ttl <= 0) {
            $expiration = -1;
        } else {
            $expiration = $ttl + time();
        }

        return $expiration;
    }

    /**
     * Normalizes cache TTL handling `null` value and {@see DateInterval} objects.
     * @param int|DateInterval|null $ttl raw TTL.
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     */
    protected function normalizeTtl($ttl): ?int
    {
        if ($ttl === null) {
            return $this->defaultTtl;
        }

        if ($ttl instanceof DateInterval) {
            try {
                return (new DateTime('@0'))->add($ttl)->getTimestamp();
            } catch (Exception $e) {
                return $this->defaultTtl;
            }
        }

        return $ttl;
    }

    protected function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable) : (array)$iterable;
    }
}
