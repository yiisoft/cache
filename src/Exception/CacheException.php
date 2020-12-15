<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Exception;

use RuntimeException;
use Throwable;

abstract class CacheException extends RuntimeException implements \Psr\SimpleCache\CacheException
{
    private string $key;

    public function __construct(string $key, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->key = $key;
        parent::__construct($message, $code, $previous);
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
