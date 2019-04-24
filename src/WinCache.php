<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache;

/**
 * WinCache provides Windows Cache caching in terms of an application component.
 *
 * To use this application component, the [WinCache PHP extension](http://www.iis.net/expand/wincacheforphp)
 * must be loaded. Also note that "wincache.ucenabled" should be set to "On" in your php.ini file.
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
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class WinCache extends SimpleCache
{
    /**
     * {@inheritdoc}
     */
    public function has($key): bool
    {
        return wincache_ucache_exists($this->normalizeKey($key));
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue($key)
    {
        return wincache_ucache_get($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function getValues($keys): array
    {
        return wincache_ucache_get($keys);
    }

    /**
     * {@inheritdoc}
     */
    protected function setValue($key, $value, $ttl): bool
    {
        return wincache_ucache_set($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    protected function setValues($values, $ttl): bool
    {
        return wincache_ucache_set($values, null, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteValue($key): bool
    {
        return wincache_ucache_delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return wincache_ucache_clear();
    }
}
