<?php
namespace Yiisoft\Cache;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\Serializer\PhpSerializer;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * SimpleCache is the base class for cache classes implementing pure PSR-16 {@see \Psr\SimpleCache\CacheInterface}.
 * This class handles cache key normalization, default TTL specification normalization, data serialization.
 *
 * Derived classes should implement the following methods which do the actual cache storage operations:
 *
 * - {@see SimpleCache::hasValue()}: check if value with a key exists in cache
 * - {@see SimpleCache::getValue()}: retrieve the value with a key (if any) from cache
 * - {@see SimpleCache::setValue()}: store the value with a key into cache
 * - {@see SimpleCache::deleteValue()}: delete the value with the specified key from cache
 * - {@see SimpleCache::clear()}: delete all values from cache
 *
 * Additionally, you may override the following methods in case backend supports getting any/or setting multiple keys
 * at once:
 *
 * - {@see SimpleCache::getValues()}: retrieve multiple values from cache
 * - {@see SimpleCache::setValues()}: store multiple values into cache
 * - {@see SimpleCache::deleteValues()}: delete multiple values from cache
 *
 * Check [PSR-16 specification](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-16-simple-cache.md)
 * before implementing your own backend.
 */
abstract class SimpleCache implements PsrCacheInterface
{
    /**
     * @var int|null default TTL for a cache entry. null meaning infinity, negative or zero results in cache key deletion.
     * This value is used by {@see set()} and {@see setMultiple()}, if the duration is not explicitly given.
     */
    private $defaultTtl;

    /**
     * @var string a string prefixed to every cache key so that it is unique globally in the whole cache storage.
     * It is recommended that you set a unique cache key prefix for each application if the same cache
     * storage is being used by different applications.
     */
    private $keyPrefix = '';

    /**
     * @var SerializerInterface the serializer to be used for serializing and unserializing of the cached data.
     */
    private $serializer;

    /**
     * @var SerializerInterface the serializer to be used for serializing and unserializing of the cached data.
     * Serializer should be an instance of {@see SerializerInterface} or its DI compatible configuration.
     * @see setSerializer
     */
    public function __construct(?SerializerInterface $serializer = null)
    {
        $this->setSerializer($serializer ?? $this->createDefaultSerializer());
    }

    /**
     * Creates a default serializer, when nothing is given
     * @return SerializerInterface
     */
    protected function createDefaultSerializer(): SerializerInterface
    {
        return new PhpSerializer();
    }

    /**
     * @var SerializerInterface the serializer to be used for serializing and unserializing of the cached data.
     *
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function get($key, $default = null)
    {
        $key = $this->normalizeKey($key);
        $value = $this->getValue($key, $default);
        if ($value === $default) {
            return $default;
        }

        return $this->serializer->unserialize($value);
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $keyMap = [];
        foreach ($keys as $originalKey) {
            $keyMap[$originalKey] = $this->normalizeKey($originalKey);
        }
        $values = $this->getValues(array_values($keyMap), $default);
        $results = [];
        foreach ($keyMap as $originalKey => $normalizedKey) {
            $results[$originalKey] = $default;
            if (isset($values[$normalizedKey]) && $values[$normalizedKey] !== $default) {
                $results[$originalKey] = $this->serializer->unserialize($values[$normalizedKey]);
            }
        }
        return $results;
    }

    public function has($key): bool
    {
        $key = $this->normalizeKey($key);
        return $this->hasValue($key);
    }

    abstract protected function hasValue(string $key): bool;

    public function set($key, $value, $ttl = null): bool
    {
        if ($ttl !== null && $ttl <= 0) {
            return $this->delete($key);
        }

        $value = $this->serializer->serialize($value);
        $key = $this->normalizeKey($key);
        $ttl = $this->normalizeTtl($ttl);
        return $this->setValue($key, $value, $ttl);
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $data = [];
        foreach ($values as $key => $value) {
            if ($ttl !== null && $ttl <= 0) {
                return $this->delete($key);
            }

            $value = $this->serializer->serialize($value);
            $key = $this->normalizeKey($key);
            $data[$key] = $value;
        }
        return $this->setValues($data, $this->normalizeTtl($ttl));
    }

    public function delete($key): bool
    {
        $key = $this->normalizeKey($key);
        return $this->deleteValue($key);
    }

    public function deleteMultiple($keys): bool
    {
        $normalizedKeys = [];
        foreach ($keys as $key) {
            $normalizedKeys[] = $this->normalizeKey($key);
        }

        return $this->deleteValues($normalizedKeys);
    }

    /**
     * Builds a normalized cache key from a given key.
     *
     * The given key will be type-casted to string.
     * If the result string does not contain alphanumeric characters only or has more than 32 characters,
     * then the hash of the key will be used.
     * The result key will be returned back prefixed with {@see $keyPrefix}.
     *
     * @param mixed $key the key to be normalized
     * @return string the generated cache key
     */
    private function normalizeKey($key): string
    {
        $key = (string)$key;
        $key = ctype_alnum($key) && \strlen($key) <= 32 ? $key : md5($key);
        return $this->keyPrefix . $key;
    }

    /**
     * Normalizes cache TTL handling `null` value and {@see \DateInterval} objects.
     * @param int|\DateInterval|null $ttl raw TTL.
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     * @throws \Exception
     */
    protected function normalizeTtl($ttl): ?int
    {
        if ($ttl === null) {
            return $this->defaultTtl;
        }
        if ($ttl instanceof \DateInterval) {
            return (new \DateTime('@0'))->add($ttl)->getTimestamp();
        }
        return $ttl;
    }

    /**
     * Retrieves a value from cache with a specified key.
     * This method should be implemented by child classes to retrieve the data
     * from specific cache storage.
     * @param string $key a unique key identifying the cached value
     * @param mixed $default default value to return if value is not in the cache or expired
     * @return mixed the value stored in cache. $default is returned if the value is not in the cache or expired. Most often
     * value is a string. If you have disabled {@see $serializer}}, it could be something else.
     */
    abstract protected function getValue(string $key, $default = null);

    /**
     * Stores a value identified by a key in cache.
     * This method should be implemented by child classes to store the data
     * in specific cache storage.
     * @param string $key the key identifying the value to be cached
     * @param mixed $value the value to be cached. Most often it's a string. If you have disabled {@see $serializer},
     * it could be something else.
     * @param int|null $ttl the number of seconds in which the cached value will expire. Null means infinity.
     * Negative value will result in deleting a value.
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    abstract protected function setValue(string $key, $value, ?int $ttl): bool;

    /**
     * Deletes a value with the specified key from cache
     * This method should be implemented by child classes to delete the data from actual cache storage.
     * @param string $key the key of the value to be deleted
     * @return bool if no error happens during deletion
     */
    abstract protected function deleteValue(string $key): bool;

    /**
     * Deletes multiple values from cache
     * This method should be overridden by child classes in case storage supports efficient deletion of multiple keys
     * @param iterable $keys
     * @return bool if no error happens during deletion
     */
    protected function deleteValues(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->deleteValue($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Retrieves multiple values from cache with the specified keys.
     * The default implementation calls {@see getValue()} multiple times to retrieve
     * the cached values one by one. If the underlying cache storage supports multiget,
     * this method should be overridden to exploit that feature.
     *
     * @param iterable $keys a list of keys identifying the cached values
     * @param mixed $default default value to return if value is not in the cache or expired
     * @return iterable a list of cached values indexed by the keys
     */
    protected function getValues(iterable $keys, $default = null): iterable
    {
        $results = [];
        foreach ($keys as $key) {
            $value = $this->getValue($key, $default);
            if ($value !== false) {
                $results[$key] = $value;
            }
        }
        return $results;
    }

    /**
     * Stores multiple key-value pairs in cache.
     * The default implementation calls {@see setValue()} multiple times store values one by one. If the underlying cache
     * storage supports multi-set, this method should be overridden to exploit that feature.
     *
     * @param iterable $values a list where key corresponds to cache key while value is the value stored
     * @param int|null $ttl the number of seconds in which the cached values will expire. Null means infinity.
     * Negative value will result in deleting a value.
     * @return bool `true` on success and `false` on failure.
     */
    protected function setValues(iterable $values, ?int $ttl): bool
    {
        $result = true;
        foreach ($values as $key => $value) {
            if (!$this->setValue($key, $value, $ttl)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * @param int|null $defaultTtl default TTL for a cache entry. null meaning infinity, negative or zero results in cache key deletion.
     * This value is used by {@see set()} and {@see setMultiple()}, if the duration is not explicitly given.
     */
    public function setDefaultTtl(?int $defaultTtl): void
    {
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * @param string $keyPrefix a string prefixed to every cache key so that it is unique globally in the whole cache storage.
     * It is recommended that you set a unique cache key prefix for each application if the same cache
     * storage is being used by different applications.
     */
    public function setKeyPrefix(string $keyPrefix): void
    {
        if (!ctype_alnum($keyPrefix)) {
            throw new InvalidArgumentException('Cache key prefix should be alphanumeric');
        }
        $this->keyPrefix = $keyPrefix;
    }
}
