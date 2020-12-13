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
    /** @var mixed */
    private $value;
    private ?int $expiry;
    private ?Dependency $dependency;
    private float $updated;

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $expiry
     * @param Dependency|null $dependency
     */
    public function __construct(string $key, $value, ?int $expiry, ?Dependency $dependency)
    {
        $this->key = $key;
        $this->value = $value;
        $this->expiry = $expiry;
        $this->dependency = $dependency;
        $this->updated = microtime(true);
    }

    /**
     * @param mixed $value
     * @param int|null $expiry
     * @param Dependency|null $dependency
     */
    public function update($value, ?int $expiry, ?Dependency $dependency): void
    {
        $this->value = $value;
        $this->expiry = $expiry;
        $this->dependency = $dependency;
        $this->updated = microtime(true);
    }

    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    public function expiry(): ?int
    {
        return $this->expiry;
    }

    public function dependency(): ?Dependency
    {
        return $this->dependency;
    }

    public function expired(float $beta, CacheInterface $cache): bool
    {
        if ($beta < 0) {
            throw new InvalidArgumentException(sprintf(
                'Argument "$beta" must be a positive number, %f given.',
                $beta
            ));
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

        return $this->expiry <= $expired || ($this->dependency !== null && $this->dependency->isChanged($cache));
    }
}
