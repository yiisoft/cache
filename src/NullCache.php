<?php

namespace Yiisoft\Cache;

use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * NullCache does not cache anything reporting success for all methods calls.
 *
 * By replacing it with some other cache component, one can quickly switch from
 * non-caching mode to caching mode.
 */
final class NullCache implements CacheInterface
{
    public function add($key, $value, $ttl = 0, Dependency $dependency = null): bool
    {
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        return true;
    }

    public function set($key, $value, $ttl = null, Dependency $dependency = null): bool
    {
        return true;
    }

    public function get($key, $default = null)
    {
        return $default;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        return array_fill_keys($this->iterableToArray($keys), $default);
    }

    public function setMultiple($values, $ttl = null, Dependency $dependency = null): bool
    {
        return true;
    }

    public function addMultiple(array $values, $ttl = null, Dependency $dependency = null): bool
    {
        return true;
    }

    public function getOrSet($key, callable $callable, $ttl = null, Dependency $dependency = null)
    {
        return $callable($this);
    }

    public function delete($key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function has($key): bool
    {
        return false;
    }

    public function enableKeyNormalization(): void
    {
    }

    public function disableKeyNormalization(): void
    {
    }

    public function setKeyPrefix(string $keyPrefix): void
    {
    }

    public function setSerializer(?SerializerInterface $serializer): void
    {
    }

    private function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable) : (array)$iterable;
    }
}
