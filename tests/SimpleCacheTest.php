<?php
namespace Yiisoft\Cache\Tests;

use DateInterval;
use Yiisoft\Cache\SimpleCache;

/**
 * @group caching
 */
class SimpleCacheTest extends TestCase
{
    /**
     * @var SimpleCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->getMockBuilder(SimpleCache::class)->getMockForAbstractClass();
    }

    /**
     * Data provider for {@see testNormalizeTtl()}
     * @return array test data
     *
     * @throws \Exception
     */
    public function dataProviderNormalizeTtl(): array
    {
        return [
            [123, 123],
            ['123', 123],
            [null, 9999],
            [0, 0],
            [new DateInterval('PT6H8M'), (6 * 3600 + 8 * 60)],
            [new DateInterval('P2Y4D'), (2 * 365 * 24 * 3600 + 4 * 24 * 3600)],
        ];
    }

    /**
     * @dataProvider dataProviderNormalizeTtl
     *
     * @covers \Yiisoft\Cache\SimpleCache::normalizeTtl()
     *
     * @param mixed $ttl
     * @param int $expectedResult
     * @throws \ReflectionException
     */
    public function testNormalizeTtl($ttl, int $expectedResult): void
    {
        $this->cache->setDefaultTtl(9999);
        $this->assertEquals($expectedResult, $this->invokeMethod($this->cache, 'normalizeTtl', [$ttl]));
    }
}
