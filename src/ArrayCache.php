<?php declare(strict_types=1);

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Exception\InvalidArgumentException;

/**
 * ArrayCache provides caching for the current request only by storing the values in an array.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that ArrayCache supports.
 */
final class ArrayCache implements CacheInterface
{
    private const EXPIRATION_INFINITY = 0;
    private const EXPIRATION_EXPIRED = -1;

    private $cache = [];

    public function get($key, $default = null)
    {
        $this->validateKey($key);
        if (isset($this->cache[$key]) && !$this->isExpired($key)) {
            $value = $this->cache[$key][0];
            if (is_object($value)) {
                $value = clone $value;
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
        $this->validateKey($keys, true);
        $results = [];
        foreach ($keys as $key) {
            $key = (string)$key;
            $value = $this->get($key, $default);
            $results[$key] = $value;
        }
        return $results;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $this->validateKey($values, true, true);
        foreach ($values as $key => $value) {
            $this->set((string)$key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        $this->validateKey($keys, true);
        foreach ($keys as $key) {
            $this->delete((string)$key);
        }
        return true;
    }

    public function has($key): bool
    {
        $this->validateKey($key);
        return isset($this->cache[$key]) && !$this->isExpired($key);
    }

    /**
     * Checks whether item is expired or not
     * @param string $key
     * @return bool
     */
    private function isExpired(string $key): bool
    {
        return $this->cache[$key][1] !== 0 && $this->cache[$key][1] <= time();
    }

    /**
     * Converts TTL to expiration
     * @param int|DateInterval|null $ttl
     * @return int
     */
    private function ttlToExpiration($ttl): int
    {
        $ttl = $this->normalizeTtl($ttl);

        if ($ttl === null) {
            $expiration = static::EXPIRATION_INFINITY;
        } elseif ($ttl <= 0) {
            $expiration = static::EXPIRATION_EXPIRED;
        } else {
            $expiration = $ttl + time();
        }

        return $expiration;
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection DateTime won't throw exception because constant string is passed as time
     *
     * Normalizes cache TTL handling strings and {@see DateInterval} objects.
     * @param int|string|DateInterval|null $ttl raw TTL.
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     */
    private function normalizeTtl($ttl): ?int
    {
        if ($ttl instanceof DateInterval) {
            return (new DateTime('@0'))->add($ttl)->getTimestamp();
        }

        if (is_string($ttl)) {
            return (int)$ttl;
        }

        return $ttl;
    }

    /**
     * Checks whether key is a legal value or not
     * @param mixed $key Key or array of keys ([key1, key2] or [key1 => val1, key2 => val2]) to be validated
     * @param bool $multiple Set to true if $key is an array of the following format [key1, key2]
     * @param bool $withValues Set to true if $key is an array of the following format [key1 => val1, key2 => val2]
     */
    private function validateKey($key, $multiple = false, $withValues = false): void
    {
        if ($multiple && !is_iterable($key)) {
            throw new InvalidArgumentException('Invalid $key value.');
        }
        if ($multiple && !$withValues) {
            foreach ($key as $item) {
                if (!\is_string($item) && !\is_int($item)) {
                    throw new InvalidArgumentException('Invalid $key value.');
                }
            }
        }
        if (!$multiple && !\is_string($key)) {
            throw new InvalidArgumentException('Invalid $key value.');
        }
    }
}
