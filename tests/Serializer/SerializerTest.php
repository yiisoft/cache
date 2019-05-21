<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Tests\Serializer;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * @group serialize
 */
abstract class SerializerTest extends TestCase
{
    /**
     * Creates serializer instance for the tests.
     * @return SerializerInterface
     */
    abstract protected function createSerializer(): SerializerInterface;

    /**
     * Data provider for [[testSerialize()]]
     * @return array test data.
     */
    public function dataProviderSerialize(): array
    {
        return [
            ['some-string'],
            [345],
            [56.89],
            [['some' => 'array']],
        ];
    }

    /**
     * @dataProvider dataProviderSerialize
     *
     * @param mixed $value
     */
    public function testSerialize($value): void
    {
        $serializer = $this->createSerializer();

        $serialized = $serializer->serialize($value);
        $this->assertIsString($serialized);

        $this->assertEquals($value, $serializer->unserialize($serialized));
    }
}
