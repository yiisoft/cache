<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Tests\Serializer;

use Yiisoft\Cache\Serializer\IgbinarySerializer;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * @group serialize
 */
class IgbinarySerializerTest extends SerializerTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!function_exists('igbinary_serialize')) {
            $this->markTestSkipped('igbinary extension is required.');
            return;
        }

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function createSerializer(): SerializerInterface
    {
        return new IgbinarySerializer();
    }
}
