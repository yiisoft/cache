<?php
namespace Yiisoft\Cache\Tests\Serializer;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * @group serialize
 */
abstract class SerializerTest extends TestCase
{
    abstract protected function createSerializer(): SerializerInterface;

    /**
     * Data provider for {@see testSerialize()}
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
