<?php
namespace Yiisoft\CacheOld\Serializer;

/**
 * PhpSerializer uses native PHP `serialize()` and `unserialize()` functions for the serialization.
 */
final class PhpSerializer implements SerializerInterface
{
    public function serialize($value): string
    {
        return serialize($value);
    }

    public function unserialize(string $value)
    {
        return unserialize($value);
    }
}
