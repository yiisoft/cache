<?php
namespace Yiisoft\Cache;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\Exceptions\InvalidArgumentException;
use Yiisoft\Cache\Serializer\PhpSerializer;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * SimpleCache is the base class for cache classes implementing pure PSR-16 [[CacheInterface]].
 * This class handles cache key normalization, default TTL specification normalization, data serialization.
 *
 * Derived classes should implement the following methods which do the actual cache storage operations:
 *
 * - [[getValue()]]: retrieve the value with a key (if any) from cache
 * - [[setValue()]]: store the value with a key into cache
 * - [[deleteValue()]]: delete the value with the specified key from cache
 * - [[clear()]]: delete all values from cache
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview)
 * and [PSR-16 specification](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-16-simple-cache.md).
 */
abstract class SimpleCache implements PsrCacheInterface
{
    /**
     * @var int default TTL for a cache entry. Default value is 0, meaning infinity.
     * This value is used by [[set()]] and [[setMultiple()]], if the duration is not explicitly given.
     */
    private $defaultTtl = 0;
    /**
     * @var string a string prefixed to every cache key so that it is unique globally in the whole cache storage.
     * It is recommended that you set a unique cache key prefix for each application if the same cache
     * storage is being used by different applications.
     *
     * To ensure interoperability, only alphanumeric characters should be used.
     */
    private $keyPrefix = '';
    /**
     * @var SerializerInterface the serializer to be used for serializing and unserializing of the cached data.
     *
     * You can disable serialization by setting this property to `NullSerializer`,
     * data will be directly sent to and retrieved from the underlying
     * cache component without any serialization or deserialization. You should not turn off serialization if
     * you are using [[Dependency|cache dependency]], because it relies on data serialization. Also, some
     * implementations of the cache can not correctly save and retrieve data different from a string type.
     */
    private $serializer;


    /**
     * @var SerializerInterface the serializer to be used for serializing and unserializing of the cached data.
     * Serializer should be an instance of [[SerializerInterface]] or its DI compatible configuration.
     * @see setSerializer
     */
    public function __construct(SerializerInterface $serializer = null)
    {
        $this->setSerializer($serializer ?? $this->createDefaultSerializer());
    }

    /**
     * Creates a default serializer, when nothing is given
     * @return PhpSerializer
     */
    protected function createDefaultSerializer() : PhpSerializer
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

    public function getMultiple($keys, $default = null): array
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
        $value = $this->serializer->serialize($value);
        $key = $this->normalizeKey($key);
        $ttl = $this->normalizeTtl($ttl);
        return $this->setValue($key, $value, $ttl);
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $data = [];
        foreach ($values as $key => $value) {
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
        $result = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Builds a normalized cache key from a given key.
     *
     * The given key will be type-casted to string.
     * If the result string does not contain alphanumeric characters only or has more than 32 characters,
     * then the hash of the key will be used.
     * The result key will be returned back prefixed with [[keyPrefix]].
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
     * Normalizes cache TTL handling `null` value and [[\DateInterval]] objects.
     * @param int|\DateInterval|null $ttl raw TTL.
     * @return int|float TTL value as UNIX timestamp.
     * @throws \Exception
     */
    protected function normalizeTtl($ttl): int
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
     * value is a string. If you have disabled [[serializer]], it could be something else.
     */
    abstract protected function getValue(string $key, $default = null);

    /**
     * Stores a value identified by a key in cache.
     * This method should be implemented by child classes to store the data
     * in specific cache storage.
     * @param string $key the key identifying the value to be cached
     * @param mixed $value the value to be cached. Most often it's a string. If you have disabled [[serializer]],
     * it could be something else.
     * @param int $ttl the number of seconds in which the cached value will expire.
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    abstract protected function setValue(string $key, $value, int $ttl): bool;

    /**
     * Deletes a value with the specified key from cache
     * This method should be implemented by child classes to delete the data from actual cache storage.
     * @param string $key the key of the value to be deleted
     * @return bool if no error happens during deletion
     */
    abstract protected function deleteValue(string $key): bool;

    /**
     * Retrieves multiple values from cache with the specified keys.
     * The default implementation calls [[getValue()]] multiple times to retrieve
     * the cached values one by one. If the underlying cache storage supports multiget,
     * this method should be overridden to exploit that feature.
     * @param array $keys a list of keys identifying the cached values
     * @param mixed $default default value to return if value is not in the cache or expired
     * @return array a list of cached values indexed by the keys
     */
    protected function getValues(array $keys, $default = null): array
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
     * The default implementation calls [[setValue()]] multiple times store values one by one. If the underlying cache
     * storage supports multi-set, this method should be overridden to exploit that feature.
     * @param array $values array where key corresponds to cache key while value is the value stored
     * @param int $ttl the number of seconds in which the cached values will expire.
     * @return bool `true` on success and `false` on failure.
     */
    protected function setValues(array $values, int $ttl): bool
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
     * @param int $defaultTtl
     */
    public function setDefaultTtl(int $defaultTtl): void
    {
        if ($defaultTtl < 0) {
            throw new InvalidArgumentException('TTL can not be negative.');
        }

        $this->defaultTtl = $defaultTtl;
    }

    /**
     * @param string $keyPrefix
     */
    public function setKeyPrefix(string $keyPrefix): void
    {
        $this->keyPrefix = $keyPrefix;
    }
}
