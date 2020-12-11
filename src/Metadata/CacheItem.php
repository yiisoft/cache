<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Metadata;

use Yiisoft\Cache\Exception\InvalidArgumentException;

use function ceil;
use function log;
use function microtime;
use function random_int;
use function sprintf;
use function time;

use const PHP_INT_MAX;

final class CacheItem
{
    private ?int $expiry;
    private float $created;

    public function __construct(?int $expiry)
    {
        $this->expiry = $expiry;
        $this->created = microtime(true);
    }

    public function expiry(?int $expiry): void
    {
        $this->expiry = $expiry;
    }

    public function expired(float $beta): bool
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
        $delta = ceil(1000 * ($now - $this->created)) / 1000;
        $expired = $now - $delta * $beta * log(random_int(1, PHP_INT_MAX) / PHP_INT_MAX);

        return $this->expiry <= $expired;
    }
}
