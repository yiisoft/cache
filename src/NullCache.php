<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;

/**
 * NullCache does not cache anything reporting success for all methods calls.
 *
 * By replacing it with some other cache component, one can quickly switch from
 * non-caching mode to caching mode.
 *
 * @phan-file-suppress PhanUnusedPublicFinalMethodParameter
 */
final class NullCache implements CacheInterface
{
    public function add($key, $value, $ttl = 0, Dependency $dependency = null): bool
    {
        $this->validateKey($key);
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);
        return true;
    }

    public function set($key, $value, $ttl = null, Dependency $dependency = null): bool
    {
        $this->validateKey($key);
        return true;
    }

    public function get($key, $default = null)
    {
        $this->validateKey($key);
        return $default;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);
        return array_fill_keys($keys, $default);
    }

    public function setMultiple($values, $ttl = null, Dependency $dependency = null): bool
    {
        $values = $this->iterableToArray($values);
        $this->validateKeysOfValues($values);
        return true;
    }

    public function addMultiple(array $values, $ttl = null, Dependency $dependency = null): bool
    {
        $values = $this->iterableToArray($values);
        $this->validateKeysOfValues($values);
        return true;
    }

    public function getOrSet($key, callable $callable, $ttl = null, Dependency $dependency = null)
    {
        $this->validateKey($key);
        return $callable($this);
    }

    public function delete($key): bool
    {
        $this->validateKey($key);
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function has($key): bool
    {
        $this->validateKey($key);
        return false;
    }

    public function enableKeyNormalization(): void
    {
        // do nothing
    }

    public function disableKeyNormalization(): void
    {
        // do nothing
    }

    public function setKeyPrefix(string $keyPrefix): void
    {
        // do nothing
    }

    private function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable) : (array)$iterable;
    }

    private function validateKey(string $key): void
    {
        if (strpbrk($key, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key value.');
        }
    }

    /**
     * @param array $keys
     */
    private function validateKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }

    /**
     * @param array $values
     */
    private function validateKeysOfValues(array $values): void
    {
        $keys = array_map('strval', array_keys($values));
        $this->validateKeys($keys);
    }
}
