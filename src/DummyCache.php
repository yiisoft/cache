<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache;

use yii\base\Component;

/**
 * DummyCache is a placeholder cache component.
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
 * DummyCache does not cache anything. It is provided so that one can always configure
 * a 'cache' application component and save the check of existence of `\Yii::getApp()->cache`.
 * By replacing DummyCache with some other cache component, one can quickly switch from
 * non-caching mode to caching mode.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
class DummyCache extends Component implements \Psr\SimpleCache\CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function has($key): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null): array
    {
        return array_fill_keys($keys, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys): bool
    {
        return true;
    }
}
