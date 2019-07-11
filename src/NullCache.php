<?php
namespace Yiisoft\Cache;

use Yiisoft\Cache\Dependencies\Dependency;

/**
 * NullCache is a placeholder cache component.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'cache' => [
 *             '__class' => Yiisoft\Cache\Cache::class,
 *             'handler' => [
 *                 '__class' => Yiisoft\Cache\DummyCache::class,
 *             ],
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * NullCache does not cache anything. It is provided so that one can always configure
 * a 'cache' application component and save the check of existence of `\Yii::getApp()->cache`.
 * By replacing DummyCache with some other cache component, one can quickly switch from
 * non-caching mode to caching mode.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
final class NullCache implements CacheInterface
{
    public function add($key, $value, $ttl = 0, Dependency $dependency = null): bool
    {
        return true;
    }

    public function deleteMultiple($keys)
    {
        // do nothing
    }

    public function set($key, $value, $ttl = null, Dependency $dependency = null): bool
    {
        return true;
    }

    public function get($key, $default = null)
    {
        return $default;
    }

    public function getMultiple($keys, $default = null)
    {
        return array_fill_keys($keys, $default);
    }

    public function setMultiple($values, $ttl = null, Dependency $dependency = null): bool
    {
        return true;
    }

    public function addMultiple(array $values, $ttl = 0, Dependency $dependency = null): bool
    {
        return true;
    }

    public function getOrSet($key, callable $callable, $ttl = null, Dependency $dependency = null)
    {
        return $callable($this);
    }

    public function delete($key)
    {
        // do nothing
    }

    public function clear()
    {
        return true;
    }

    public function has($key)
    {
        return false;
    }
}
