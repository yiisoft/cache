<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Metadata;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;

use function ceil;
use function log;
use function microtime;
use function random_int;
use function sprintf;
use function time;

use const PHP_INT_MAX;

/**
 * CacheItem store the metadata of cache item.
 *
 * @internal
 */
final class CacheItem
{
    private string $key;
    private ?int $expiry;
    private ?Dependency $dependency;
    private float $updated;

    /**
     * @param string $key The key that identifies the cache item.
     * @param int|null $expiry The cache expiry or null that meaning infinity.
     * @param Dependency|null $dependency The cache dependency or null if it is not assigned.
     */
    public function __construct(string $key, ?int $expiry, ?Dependency $dependency)
    {
        $this->key = $key;
        $this->expiry = $expiry;
        $this->dependency = $dependency;
        $this->updated = microtime(true);
    }

    /**
     * Updates the metadata of the cache item.
     *
     * @param int|null $expiry The cache expiry or null that meaning infinity.
     * @param Dependency|null $dependency The cache dependency or null if it is not assigned.
     */
    public function update(?int $expiry, ?Dependency $dependency): void
    {
        $this->expiry = $expiry;
        $this->dependency = $dependency;
        $this->updated = microtime(true);
    }

    /**
     * Returns a key that identifies the cache item.
     *
     * @return string The key that identifies the cache item.
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * Returns a cache expiry or null that meaning infinity.
     *
     * @return int|null The cache expiry or null that meaning infinity.
     */
    public function expiry(): ?int
    {
        return $this->expiry;
    }

    /**
     * Returns a cache dependency or null if it is not assigned.
     *
     * @return Dependency|null The cache dependency or null if it is not assigned.
     */
    public function dependency(): ?Dependency
    {
        return $this->dependency;
    }

    /**
     * Checks whether the dependency has been changed and whether the cache expires.
     *
     * @param float $beta The value for calculating the range that is used for "Probably early expiration" algorithm.
     * @param CacheInterface $cache The actual cache handler.
     *
     * @return bool Whether the dependency has been changed and whether the cache expires.
     */
    public function expired(float $beta, CacheInterface $cache): bool
    {
        if ($beta < 0) {
            throw new InvalidArgumentException(sprintf(
                'Argument "$beta" must be a positive number, %f given.',
                $beta
            ));
        }

        if ($this->dependency !== null && $this->dependency->isChanged($cache)) {
            return true;
        }

        if ($this->expiry === null) {
            return false;
        }

        if ($this->expiry <= time()) {
            return true;
        }

        $now = microtime(true);
        $delta = ceil(1000 * ($now - $this->updated)) / 1000;
        $expired = $now - $delta * $beta * log(random_int(1, PHP_INT_MAX) / PHP_INT_MAX);

        return $this->expiry <= $expired;
    }
}
