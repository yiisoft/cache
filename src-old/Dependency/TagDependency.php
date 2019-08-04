<?php
namespace Yiisoft\CacheOld\DependencyOld;

use Psr\SimpleCache\InvalidArgumentException;
use Yiisoft\CacheOld\CacheInterface;

/**
 * TagDependency associates a cached value with one or multiple {@see TagDependency::$tags}.
 *
 * By calling {@see TagDependency::invalidate()}, you can invalidate all cached values that are associated with the specified tag name(s).
 *
 * ```php
 * // setting multiple cache keys to store data forever and tagging them with "user-123"
 * $cache->set('user_42_profile', '', 0, new TagDependency('user-123'));
 * $cache->set('user_42_stats', '', 0, new TagDependency('user-123'));
 *
 * // invalidating all keys tagged with "user-123"
 * TagDependency::invalidate($cache, 'user-123');
 * ```
 */
final class TagDependency extends Dependency
{
    /**
     * @var array a list of tag names for this dependency
     */
    private $tags;

    /**
     * @param string|array $tags a list of tag names for this dependency. For a single tag, you may specify it as a string
     */
    public function __construct($tags)
    {
        $this->tags = (array)$tags;
    }

    /**
     * Generates the data needed to determine if dependency has been changed.
     * This method does nothing in this class.
     * @param CacheInterface $cache the cache component that is currently evaluating this dependency
     * @return mixed the data needed to determine if dependency has been changed.
     * @throws InvalidArgumentException
     */
    protected function generateDependencyData(CacheInterface $cache): array
    {
        $timestamps = $this->getTimestamps($cache, $this->tags);

        $newKeys = [];
        foreach ($timestamps as $key => $timestamp) {
            if ($timestamp === false) {
                $newKeys[] = $key;
            }
        }
        if (!empty($newKeys)) {
            $timestamps = array_merge($timestamps, self::touchKeys($cache, $newKeys));
        }

        return $timestamps;
    }

    public function isChanged(CacheInterface $cache): bool
    {
        $timestamps = $this->getTimestamps($cache, $this->tags);
        return $timestamps !== $this->data;
    }

    /**
     * Invalidates all of the cached values that are associated with any of the specified {@see tags}.
     * @param CacheInterface $cache the cache component that caches the values
     * @param string|array $tags
     */
    public static function invalidate(CacheInterface $cache, $tags): void
    {
        $keys = [];
        foreach ((array) $tags as $tag) {
            $keys[] = self::buildCacheKey($tag);
        }
        self::touchKeys($cache, $keys);
    }

    /**
     * Generates the timestamp for the specified cache keys.
     * @param CacheInterface $cache
     * @param string[] $keys
     * @return array the timestamp indexed by cache keys
     */
    private static function touchKeys(CacheInterface $cache, array $keys): array
    {
        $values = [];
        $time = microtime();
        foreach ($keys as $key) {
            $values[$key] = $time;
        }
        $cache->setMultiple($values);
        return $values;
    }

    /**
     * Returns the timestamps for the specified tags.
     * @param CacheInterface $cache
     * @param string[] $tags
     * @return array the timestamps indexed by the specified tags.
     * @throws InvalidArgumentException
     */
    private function getTimestamps(CacheInterface $cache, array $tags): iterable
    {
        if (empty($tags)) {
            return [];
        }

        $keys = [];
        foreach ($tags as $tag) {
            $keys[] = self::buildCacheKey($tag);
        }

        return $cache->getMultiple($keys);
    }

    /**
     * Builds a normalized cache key from a given tag, making sure it is short enough and safe
     * for any particular cache storage.
     * @param string $tag tag name.
     * @return string cache key.
     */
    private static function buildCacheKey(string $tag): string
    {
        return md5(json_encode([__CLASS__, $tag]));
    }
}
