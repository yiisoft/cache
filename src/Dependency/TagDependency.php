<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Dependency;

use Psr\SimpleCache\InvalidArgumentException;
use Yiisoft\Cache\CacheInterface;

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
     * @param CacheInterface $cache the cache component that is currently evaluating this dependency
     * @return array the data needed to determine if dependency has been changed.
     * @throws InvalidArgumentException
     * @suppress PhanTypeInvalidThrowsIsInterface
     */
    protected function generateDependencyData(CacheInterface $cache): array
    {
        $timestamps = $this->getStoredTagTimestamps($cache, $this->tags);
        $timestamps = $this->storeTimestampsForNewTags($cache, $timestamps);

        return $timestamps;
    }

    public function isChanged(CacheInterface $cache): bool
    {
        $timestamps = $this->getStoredTagTimestamps($cache, $this->tags);
        return $timestamps !== $this->data;
    }

    /**
     * Invalidates all of the cached values that are associated with any of the specified {@see tags}.
     * @param CacheInterface $cache the cache component that caches the values
     * @param string|array $tags
     */
    public static function invalidate(CacheInterface $cache, $tags): void
    {
        $keys = self::buildCacheKeys($tags);
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
     * @return iterable the timestamps indexed by the specified tags.
     * @throws InvalidArgumentException
     * @suppress PhanTypeInvalidThrowsIsInterface
     */
    private function getStoredTagTimestamps(CacheInterface $cache, array $tags): iterable
    {
        if (empty($tags)) {
            return [];
        }

        $keys = self::buildCacheKeys($tags);

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
        $jsonTag = json_encode([__CLASS__, $tag]);
        if ($jsonTag === false) {
            throw new \Yiisoft\Cache\Exception\InvalidArgumentException('Invalid tag. ' . json_last_error_msg());
        }

        return md5($jsonTag);
    }

    /**
     * Builds array of keys from a given tags
     * @param mixed $tags
     * @return array
     */
    private static function buildCacheKeys($tags): array
    {
        $keys = [];
        foreach ((array)$tags as $tag) {
            $keys[] = self::buildCacheKey((string)$tag);
        }

        return $keys;
    }

    /**
     * Generates and stores timestamps for tags that are not stored in the cache yet.
     * @param CacheInterface $cache
     * @param iterable $timestamps
     * @return array
     */
    private function storeTimestampsForNewTags(CacheInterface $cache, iterable $timestamps): array
    {
        $newKeys = [];
        foreach ($timestamps as $key => $timestamp) {
            if ($timestamp === null) {
                $newKeys[] = $key;
            }
        }
        $timestamps = $this->iterableToArray($timestamps);
        if (!empty($newKeys)) {
            $timestamps = array_merge($timestamps, self::touchKeys($cache, $newKeys));
        }

        return $timestamps;
    }
}
