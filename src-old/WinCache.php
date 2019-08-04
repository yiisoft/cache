<?php
namespace Yiisoft\CacheOld;

/**
 * WinCache provides Windows Cache caching in terms of an application component.
 *
 * To use this application component, the [WinCache PHP extension](https://sourceforge.net/projects/wincache/)
 * must be loaded. Also note that "wincache.ucenabled" should be set to "1" in your php.ini file.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that are supported by WinCache.
 */
final class WinCache extends SimpleCache
{
    private const TTL_INFINITY = 0;

    protected function hasValue(string $key): bool
    {
        return \wincache_ucache_exists($key);
    }

    protected function getValue(string $key, $default = null)
    {
        $value = \wincache_ucache_get($key, $success);
        return $success ? $value : $default;
    }

    protected function getValues(iterable $keys, $default = null): iterable
    {
        $defaultValues = array_fill_keys($keys, $default);
        return array_merge($defaultValues, \wincache_ucache_get($keys));
    }

    protected function setValue(string $key, $value, ?int $ttl): bool
    {
        return \wincache_ucache_set($key, $value, $ttl ?? self::TTL_INFINITY);
    }

    protected function setValues(iterable $values, ?int $ttl): bool
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

    public function deleteValues(iterable $keys): bool
    {
        $deleted = array_flip(\wincache_ucache_delete($keys));
        foreach ($keys as $expectedKey) {
            if (!isset($deleted[$expectedKey])) {
                return false;
            }
        }
        return true;
    }
}
