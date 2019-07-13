<?php
namespace Yiisoft\Cache;

/**
 * ArrayCache provides caching for the current request only by storing the values in an array.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that ArrayCache supports.
 */
final class ArrayCache extends SimpleCache
{
    private const TTL_INFINITY = 0;

    /**
     * @var array cached values.
     */
    private $cache = [];

    public function hasValue(string $key): bool
    {
        return isset($this->cache[$key]) && ($this->cache[$key][1] === 0 || $this->cache[$key][1] > time());
    }

    protected function getValue(string $key, $default = null)
    {
        if (isset($this->cache[$key]) && ($this->cache[$key][1] === 0 || $this->cache[$key][1] > time())) {
            return $this->cache[$key][0];
        }

        return $default;
    }

    protected function setValue(string $key, $value, ?int $ttl): bool
    {
        $this->cache[$key] = [$value, $ttl === null ? self::TTL_INFINITY : time() + $ttl];
        return true;
    }

    protected function deleteValue(string $key): bool
    {
        unset($this->cache[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }
}
