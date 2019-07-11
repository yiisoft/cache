<?php
namespace Yiisoft\Cache;

/**
 * WinCache provides Windows Cache caching in terms of an application component.
 *
 * To use this application component, the [WinCache PHP extension](https://sourceforge.net/projects/wincache/)
 * must be loaded. Also note that "wincache.ucenabled" should be set to "1" in your php.ini file.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'cache' => [
 *             '__class' => Yiisoft\Cache\Cache::class,
 *             'handler' => [
 *                 '__class' => Yiisoft\Cache\WinCache::class,
 *             ],
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * See [[\Psr\SimpleCache\CacheInterface]] for common cache operations that are supported by WinCache.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
final class WinCache extends SimpleCache
{
    private const TTL_INFINITY = 0;

    public function hasValue(string $key): bool
    {
        return \wincache_ucache_exists($key);
    }

    protected function getValue(string $key, $default = null)
    {
        $value = \wincache_ucache_get($key, $success);
        return $success ? $value : $default;
    }

    protected function getValues(array $keys, $default = null): array
    {
        $defaultValues = array_fill_keys($keys, $default);
        return array_merge($defaultValues, \wincache_ucache_get($keys));
    }

    protected function setValue(string $key, $value, ?int $ttl): bool
    {
        return \wincache_ucache_set($key, $value, $ttl ?? self::TTL_INFINITY);
    }

    protected function setValues(array $values, ?int $ttl): bool
    {
        return \wincache_ucache_set($values, null, $ttl ?? self::TTL_INFINITY) === [];
    }

    protected function deleteValue(string $key): bool
    {
        return \wincache_ucache_delete($key);
    }

    public function clear(): bool
    {
        return \wincache_ucache_clear();
    }
}
