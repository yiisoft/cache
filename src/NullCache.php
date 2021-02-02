<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use Traversable;
use Yiisoft\Cache\Exception\InvalidArgumentException;

use function array_fill_keys;
use function array_keys;
use function array_map;
use function is_string;
use function iterator_to_array;
use function strpbrk;

/**
 * NullCache does not cache anything reporting success for all methods calls.
 *
 * By replacing it with some other cache component, one can quickly switch from non-caching mode to caching mode.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that NullCache supports.
 */
final class NullCache implements \Psr\SimpleCache\CacheInterface
{
    public function get($key, $default = null)
    {
        $this->validateKey($key);
        return $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->validateKey($key);
        return true;
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

    public function getMultiple($keys, $default = null): iterable
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);
        return array_fill_keys($keys, $default);
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $values = $this->iterableToArray($values);
        $this->validateKeysOfValues($values);
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);
        return true;
    }

    public function has($key): bool
    {
        $this->validateKey($key);
        return false;
    }

    /**
     * @param mixed $iterable
     *
     * @return array
     */
    private function iterableToArray($iterable): array
    {
        return $iterable instanceof Traversable ? iterator_to_array($iterable) : (array) $iterable;
    }

    /**
     * @param mixed $key
     */
    private function validateKey($key): void
    {
        if (!is_string($key) || $key === '' || strpbrk($key, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key value.');
        }
    }

    private function validateKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }

    private function validateKeysOfValues(array $values): void
    {
        $keys = array_map('\strval', array_keys($values));
        $this->validateKeys($keys);
    }
}
