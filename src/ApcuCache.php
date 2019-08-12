<?php

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;

/**
 * ApcuCache provides APCu caching in terms of an application component.
 *
 * To use this application component, the [APCu PHP extension](http://www.php.net/apcu) must be loaded.
 * In order to enable APCu for CLI you should add "apc.enable_cli = 1" to your php.ini.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that ApcCache supports.
 */
class ApcuCache implements CacheInterface
{
    private const TTL_INFINITY = 0;
    private const TTL_EXPIRED = -1;

    public function get($key, $default = null)
    {
        $value = \apcu_fetch($key, $success);
        return $success ? $value : $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $ttl = $this->normalizeTtl($ttl);
        if ($ttl < 0) {
            return $this->delete($key);
        }
        return \apcu_store($key, $value, $ttl);
    }

    public function delete($key): bool
    {
        return \apcu_delete($key);
    }

    public function clear(): bool
    {
        return \apcu_clear_cache();
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $values = \apcu_fetch($this->iterableToArray($keys), $success) ?: [];
        return array_merge(array_fill_keys($this->iterableToArray($keys), $default), $values);
    }

    public function setMultiple($values, $ttl = null): bool
    {
        return \apcu_store($this->iterableToArray($values), null, $this->normalizeTtl($ttl)) === [];
    }

    public function deleteMultiple($keys): bool
    {
        return \apcu_delete($this->iterableToArray($keys)) === [];
    }

    public function has($key): bool
    {
        return \apcu_exists($key);
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection DateTime won't throw exception because constant string is passed as time
     *
     * Normalizes cache TTL handling `null` value and {@see DateInterval} objects.
     * @param int|DateInterval|null $ttl raw TTL.
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     */
    private function normalizeTtl($ttl): ?int
    {
        $normalizedTtl = $ttl;
        if ($ttl instanceof DateInterval) {
            $normalizedTtl = (new DateTime('@0'))->add($ttl)->getTimestamp();
        }

        return $normalizedTtl ?? static::TTL_INFINITY;
    }

    /**
     * Converts iterable to array
     * @param iterable $iterable
     * @return array
     */
    private function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable) : (array)$iterable;
    }
}
