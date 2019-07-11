<?php
namespace Yiisoft\Cache\Exceptions;

/**
 * InvalidConfigException represents an exception caused by an incorrect object configuration.
 */
class InvalidConfigException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid Configuration';
    }
}
