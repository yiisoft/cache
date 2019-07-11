<?php
namespace Yiisoft\Cache\Serializer;

/**
 * SerializerInterface defines serializer interface.
 */
interface SerializerInterface
{
    /**
     * Serializes given value.
     * @param mixed $value value to be serialized
     * @return string serialized value.
     */
    public function serialize($value): string;

    /**
     * Restores value from its serialized representations
     * @param string $value serialized string.
     * @return mixed restored value
     */
    public function unserialize(string $value);
}
