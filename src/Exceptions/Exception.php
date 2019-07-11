<?php
namespace Yiisoft\Cache\Exceptions;

/**
 * Exception represents an exception that is caused by some Caching-related operations.
 */
class Exception extends \Exception implements \Psr\SimpleCache\CacheException
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Cache Exception';
    }
}
