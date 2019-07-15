<?php
namespace Yiisoft\Cache;

/**
 * ApcuCache provides APCu caching in terms of an application component.
 *
 * To use this application component, the [APCu PHP extension](http://www.php.net/apcu) must be loaded.
 * In order to enable APCu for CLI you should add "apc.enable_cli = 1" to your php.ini.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that ApcCache supports.
 */
final class ApcuCache extends SimpleCache
{
    private const TTL_INFINITY = 0;

    protected function hasValue(string $key): bool
    {
        return \apcu_exists($key);
    }

    protected function getValue(string $key, $default = null)
    {
        $value = \apcu_fetch($key, $success);
        return $success ? $value : $default;
    }

    protected function getValues(iterable $keys, $default = null): iterable
    {
        return \apcu_fetch($keys, $success) ?: [];
    }

    protected function setValue(string $key, $value, ?int $ttl): bool
    {
        return \apcu_store($key, $value, $ttl ?? self::TTL_INFINITY);
    }

    protected function setValues(iterable $values, ?int $ttl): bool
    {
        return \apcu_store($values, null, $ttl ?? self::TTL_INFINITY) === [];
    }

    protected function deleteValue(string $key): bool
    {
        return \apcu_delete($key);
    }

    public function clear(): bool
    {
        return \apcu_clear_cache();
    }

    public function deleteValues(iterable $keys): bool
    {
        return \apcu_delete($keys) === [];
    }
}
