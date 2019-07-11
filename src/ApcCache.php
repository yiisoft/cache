<?php
namespace Yiisoft\Cache;

/**
 * ApcCache provides APCu caching in terms of an application component.
 *
 * To use this application component, the [APCu PHP extension](http://www.php.net/apcu) must be loaded.
 * In order to enable APCu for CLI you should add "apc.enable_cli = 1" to your php.ini.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'cache' => [
 *             '__class' => Yiisoft\Cache\Cache::class,
 *             'handler' => [
 *                 '__class' => Yiisoft\Cache\ApcCache::class,
 *             ],
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * See [[\Psr\SimpleCache\CacheInterface]] for common cache operations that ApcCache supports.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
final class ApcCache extends SimpleCache
{
    public function hasValue(string $key): bool
    {
        return (bool)\apcu_exists($key);
    }

    protected function getValue(string $key, $default = null)
    {
        $value = \apcu_fetch($key, $success);
        return $success ? $value : $default;
    }

    protected function getValues(array $keys, $default = null): array
    {
        // TODO: test that all keys are returned
        return \apcu_fetch($keys, $succses) ?: [];
    }

    protected function setValue(string $key, $value, int $ttl): bool
    {
        return (bool)\apcu_store($key, $value, $ttl);
    }

    protected function setValues(array $values, int $ttl): bool
    {
        return \apcu_store($values, null, $ttl) === [];
    }

    protected function deleteValue(string $key): bool
    {
        return (bool)\apcu_delete($key);
    }

    public function clear(): bool
    {
        return \apcu_clear_cache();
    }
}
