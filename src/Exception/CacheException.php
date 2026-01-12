<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Exception;

use Psr\SimpleCache\CacheException as PsrCacheException;
use RuntimeException;
use Throwable;

abstract class CacheException extends RuntimeException implements PsrCacheException
{
    public function __construct(
        private readonly string $key,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
