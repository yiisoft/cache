<?php
namespace Yiisoft\Cache\Serializer;

/**
 * NullSerializer returns input unchanged.
 */
class NullSerializer implements SerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize($value): string
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize(string $value)
    {
        return $value;
    }
}
