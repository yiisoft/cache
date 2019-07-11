<?php
namespace Yiisoft\Cache\Serializer;

/**
 * NullSerializer returns input unchanged.
 */
class NullSerializer implements SerializerInterface
{
    public function serialize($value): string
    {
        return $value;
    }

    public function unserialize(string $value): string
    {
        return $value;
    }
}
