<?php
namespace Yiisoft\Cache\Serializer;

/**
 * PhpSerializer uses native PHP `serialize()` and `unserialize()` functions for the serialization.
 */
class PhpSerializer implements SerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize($value): string
    {
        return serialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize(string $value)
    {
        return unserialize($value);
    }
}
