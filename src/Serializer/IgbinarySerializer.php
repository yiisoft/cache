<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Serializer;

/**
 * IgbinarySerializer uses [Igbinary PHP extension](http://pecl.php.net/package/igbinary) for serialization.
 * Make sure you have 'igbinary' PHP extension install at your system before attempt to use this serializer.
 */
class IgbinarySerializer implements SerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize($value): string
    {
        return igbinary_serialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize(string $value)
    {
        return igbinary_unserialize($value);
    }
}
