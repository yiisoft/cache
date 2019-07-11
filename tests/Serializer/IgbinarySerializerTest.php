<?php
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
