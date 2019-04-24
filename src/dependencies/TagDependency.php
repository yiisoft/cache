<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Dependencies;

/**
 * TagDependency associates a cached data item with one or multiple [[tags]].
 *
 * By calling [[invalidate()]], you can invalidate all cached data items that are associated with the specified tag name(s).
 *
 * ```php
 * // setting multiple cache keys to store data forever and tagging them with "user-123"
 * Yii::getApp()->cache->set('user_42_profile', '', 0, new TagDependency(['tags' => 'user-123']));
 * Yii::getApp()->cache->set('user_42_stats', '', 0, new TagDependency(['tags' => 'user-123']));
 *
 * // invalidating all keys tagged with "user-123"
 * TagDependency::invalidate(Yii::getApp()->cache, 'user-123');
 * ```
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
class TagDependency extends Dependency
{
    /**
     * @var string|array a list of tag names for this dependency. For a single tag, you may specify it as a string.
     */
    public $tags;


    /**
     * @param string|array $tags a list of tag names for this dependency. For a single tag, you may specify it as a string.
     */
    public function __construct($tags)
    {
        $this->tags = $tags;
    }

    /**
     * Generates the data needed to determine if dependency has been changed.
     * This method does nothing in this class.
     * @param \Yiisoft\Cache\CacheInterface $cache the cache component that is currently evaluating this dependency
     * @return mixed the data needed to determine if dependency has been changed.
     */
    protected function generateDependencyData($cache)
    {
        $timestamps = $this->getTimestamps($cache, (array) $this->tags);

        $newKeys = [];
        foreach ($timestamps as $key => $timestamp) {
            if ($timestamp === false) {
                $newKeys[] = $key;
            }
        }
        if (!empty($newKeys)) {
            $timestamps = array_merge($timestamps, static::touchKeys($cache, $newKeys));
        }

        return $timestamps;
    }

    /**
     * {@inheritdoc}
     */
    public function isChanged($cache): bool
    {
        $timestamps = $this->getTimestamps($cache, (array) $this->tags);
        return $timestamps !== $this->data;
    }

    /**
     * Invalidates all of the cached data items that are associated with any of the specified [[tags]].
     * @param \Psr\SimpleCache\CacheInterface $cache the cache component that caches the data items
     * @param string|array $tags
     */
    public static function invalidate($cache, $tags): void
    {
        $keys = [];
        foreach ((array) $tags as $tag) {
            $keys[] = static::buildCacheKey($tag);
        }
        static::touchKeys($cache, $keys);
    }

    /**
     * Generates the timestamp for the specified cache keys.
     * @param \Psr\SimpleCache\CacheInterface $cache
     * @param string[] $keys
     * @return array the timestamp indexed by cache keys
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected static function touchKeys($cache, $keys): array
    {
        $items = [];
        $time = microtime();
        foreach ($keys as $key) {
            $items[$key] = $time;
        }
        $cache->setMultiple($items);
        return $items;
    }

    /**
     * Returns the timestamps for the specified tags.
     * @param \Psr\SimpleCache\CacheInterface $cache
     * @param string[] $tags
     * @return array the timestamps indexed by the specified tags.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function getTimestamps($cache, $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        $keys = [];
        foreach ($tags as $tag) {
            $keys[] = static::buildCacheKey($tag);
        }

        return $cache->getMultiple($keys);
    }

    /**
     * Builds a normalized cache key from a given tag, making sure it is short enough and safe
     * for any particular cache storage.
     * @param string $tag tag name.
     * @return string cache key.
     */
    protected static function buildCacheKey($tag): string
    {
        return md5(json_encode([__CLASS__, $tag]));
    }
}
