<?php
namespace Yiisoft\Cache\Serializer;

/**
 * IgbinarySerializer uses [Igbinary PHP extension](http://pecl.php.net/package/igbinary) for serialization.
 * Make sure you have 'igbinary' PHP extension install at your system before attempt to use this serializer.
 */
final class IgbinarySerializer implements SerializerInterface
{
    public function serialize($value): string
    {
        return igbinary_serialize($value);
    }

    public function unserialize(string $value)
    {
        return igbinary_unserialize($value);
    }
}
