<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Traversable;
use Yiisoft\Cache\Exception\InvalidArgumentException;

use function array_keys;
use function array_map;
use function gettype;
use function is_iterable;
use function is_object;
use function is_string;
use function iterator_to_array;
use function strpbrk;
use function time;

/**
 * ArrayCache provides caching for the current request only by storing the values in an array.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that ArrayCache supports.
 */
final class ArrayCache implements \Psr\SimpleCache\CacheInterface
{
    private const EXPIRATION_INFINITY = 0;
    private const EXPIRATION_EXPIRED = -1;

    private array $cache = [];

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            $value = $this->cache[$key][0];

            if (is_object($value)) {
                return clone $value;
            }

            return $value;
        }

        return $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->validateKey($key);
        $expiration = $this->ttlToExpiration($ttl);

        if ($expiration < 0) {
            return $this->delete($key);
        }

        if (is_object($value)) {
            $value = clone $value;
        }

        $this->cache[$key] = [$value, $expiration];
        return true;
    }

    public function delete($key): bool
    {
        $this->validateKey($key);
        unset($this->cache[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);
        $results = [];

        foreach ($keys as $key) {
            $value = $this->get($key, $default);
            $results[$key] = $value;
        }

        return $results;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $values = $this->iterableToArray($values);
        $this->validateKeysOfValues($values);

        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);

        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key): bool
    {
        $this->validateKey($key);
        return !$this->isExpired($key);
    }

    /**
     * Checks whether item is expired or not
     *
     * @param string $key
     *
     * @return bool
     */
    private function isExpired(string $key): bool
    {
        return !isset($this->cache[$key]) || ($this->cache[$key][1] !== 0 && $this->cache[$key][1] <= time());
    }

    /**
     * Converts TTL to expiration
     *
     * @param DateInterval|int|null $ttl
     *
     * @return int
     */
    private function ttlToExpiration($ttl): int
    {
        $ttl = $this->normalizeTtl($ttl);

        if ($ttl === null) {
            return self::EXPIRATION_INFINITY;
        }

        if ($ttl <= 0) {
            return self::EXPIRATION_EXPIRED;
        }

        return $ttl + time();
    }

    /**
     * Normalizes cache TTL handling strings and {@see DateInterval} objects.
     *
     * @param DateInterval|int|string|null $ttl Raw TTL.
     *
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     */
    private function normalizeTtl($ttl): ?int
    {
        if ($ttl instanceof DateInterval) {
            return (new DateTime('@0'))->add($ttl)->getTimestamp();
        }

        if ($ttl === null) {
            return null;
        }

        return (int) $ttl;
    }

    /**
     * Converts iterable to array. If provided value is not iterable it throws an InvalidArgumentException
     *
     * @param mixed $iterable
     *
     * @return array
     */
    private function iterableToArray($iterable): array
    {
        if (!is_iterable($iterable)) {
            throw new InvalidArgumentException('Iterable is expected, got ' . gettype($iterable));
        }

        /** @psalm-suppress RedundantCast */
        return $iterable instanceof Traversable ? iterator_to_array($iterable) : (array) $iterable;
    }

    /**
     * @param mixed $key
     */
    private function validateKey($key): void
    {
        if (!is_string($key) || $key === '' || strpbrk($key, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key value.');
        }
    }

    /**
     * @param array $keys
     */
    private function validateKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }

    private function validateKeysOfValues(array $values): void
    {
        $keys = array_map('\strval', array_keys($values));
        $this->validateKeys($keys);
    }
}
