<?php
namespace Yiisoft\Cache\Tests\Serializer;

use Yiisoft\Cache\Serializer\CallbackSerializer;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * @group serialize
 */
class CallbackSerializerTest extends SerializerTest
{
    /**
     * {@inheritdoc}
     */
    protected function createSerializer(): SerializerInterface
    {
        return new CallbackSerializer('serialize', 'unserialize');
    }
}
