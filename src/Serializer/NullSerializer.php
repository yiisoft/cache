<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

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
