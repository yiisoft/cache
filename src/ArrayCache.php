<?php
namespace Yiisoft\Cache;

/**
 * ArrayCache provides caching for the current request only by storing the values in an array.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'cache' => [
 *             '__class' => Yiisoft\Cache\Cache::class,
 *             'handler' => [
 *                 '__class' => Yiisoft\Cache\ArrayCache::class,
 *             ],
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * See [[\Psr\SimpleCache\CacheInterface]] for common cache operations that ArrayCache supports.
 *
 * Unlike the [[Cache]], ArrayCache allows the expire parameter of [[set()]] and [[setMultiple()]]  to
 * be a floating point number, so you may specify the time in milliseconds (e.g. 0.1 will be 100 milliseconds).
 *
 * For enhanced performance of ArrayCache, you can disable serialization of the stored data by setting [[$serializer]] to `false`.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
class ArrayCache extends SimpleCache
{
    /**
     * @var array cached values.
     */
    private $cache = [];

    public function hasValue($key): bool
    {
        return isset($this->cache[$key]) && ($this->cache[$key][1] === 0 || $this->cache[$key][1] > microtime(true));
    }

    protected function getValue($key, $default = null)
    {
        if (isset($this->cache[$key]) && ($this->cache[$key][1] === 0 || $this->cache[$key][1] > microtime(true))) {
            return $this->cache[$key][0];
        }

        return $default;
    }

    protected function setValue($key, $value, $ttl): bool
    {
        $this->cache[$key] = [$value, $ttl === 0 ? 0 : microtime(true) + $ttl];
        return true;
    }

    protected function deleteValue($key): bool
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
