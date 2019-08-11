<?php

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Exception;
use Psr\SimpleCache\CacheInterface;

/**
 * WinCache provides Windows Cache caching in terms of an application component.
 *
 * To use this application component, the [WinCache PHP extension](https://sourceforge.net/projects/wincache/)
 * must be loaded. Also note that "wincache.ucenabled" should be set to "1" in your php.ini file.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that are supported by WinCache.
 */
class WinCache implements CacheInterface
{
    private const TTL_INFINITY = 0;
    private const TTL_EXPIRED = -1;

    public function get($key, $default = null)
    {
        $value = \wincache_ucache_get($key, $success);
        return $success ? $value : $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $ttl = $this->normalizeTtl($ttl);
        if ($ttl < 0) {
            return $this->delete($key);
        }
        return \wincache_ucache_set($key, $value, $ttl);
    }

    public function delete($key)
    {
        return \wincache_ucache_delete($key);
    }

    public function clear()
    {
        return \wincache_ucache_clear();
    }

    public function getMultiple($keys, $default = null)
    {
        $defaultValues = array_fill_keys($this->iterableToArray($keys), $default);
        return array_merge($defaultValues, \wincache_ucache_get($this->iterableToArray($keys)));
    }

    public function setMultiple($values, $ttl = null)
    {
        return \wincache_ucache_set($this->iterableToArray($values), null, $this->normalizeTtl($ttl)) === [];
    }

    public function deleteMultiple($keys)
    {
        $deleted = array_flip(\wincache_ucache_delete($keys));
        foreach ($keys as $expectedKey) {
            if (!isset($deleted[$expectedKey])) {
                return false;
            }
        }
        return true;
    }

    public function has($key)
    {
        return \wincache_ucache_exists($key);
    }

    /**
     * Normalizes cache TTL handling `null` value and {@see DateInterval} objects.
     * @param int|DateInterval|null $ttl raw TTL.
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     */
    private function normalizeTtl($ttl): ?int
    {
        $normalizedTtl = $ttl;
        if ($ttl instanceof DateInterval) {
            try {
                $normalizedTtl = (new DateTime('@0'))->add($ttl)->getTimestamp();
            } catch (Exception $e) {
                $normalizedTtl = self::TTL_EXPIRED;
            }
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
