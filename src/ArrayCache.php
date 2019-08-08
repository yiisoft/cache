<?php

namespace Yiisoft\Cache;

/**
 * ArrayCache provides caching for the current request only by storing the values in an array.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that ArrayCache supports.
 */
final class ArrayCache extends BaseCache
{
    private $cache = [];

    public function get($key, $default = null)
    {
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
        unset($this->cache[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        $results = [];
        foreach ($keys as $key) {
            $value = $this->get($key, $default);
            $results[$key] = $value;
        }
        return $results;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key): bool
    {
        return isset($this->cache[$key]) && !$this->isExpired($key);
    }

    /**
     * Checks whether item is expired or not
     * @param $key
     * @return bool
     */
    private function isExpired($key): bool
    {
        return $this->cache[$key][1] !== 0 && $this->cache[$key][1] <= time();
    }
}
