<?php
namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependencies\TagDependency;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
/**
 * Trait IntegrationTestTrait
 * This trait contains methods to test interaction of Cache + CacheInterface implementation
 * e.g. it tests that underlying implementation works the same manner as a delegate
 * @package Yiisoft\Cache\Tests
 */
trait IntegrationTestTrait
{
    /**
     * Returns data for integration test of Cache class
     */
    public function cacheIntegrationProvider()
    {
        return [
            [new Cache($this->getTestScopeInstance())],
        ];
    }

    /**
     * Returns data for integration test, prepared
     */
    public function preparedIntegrationCacheProvider()
    {
        return [
            [$this->prepare(new Cache($this->getTestScopeInstance()))]
        ];
    }

    /**
     * @dataProvider cacheIntegrationProvider
     */
    public function testArrayAccess(CacheInterface $cache)
    {
        $cache['arrayaccess_test'] = new \stdClass();
        $this->assertInstanceOf('stdClass', $cache['arrayaccess_test']);
    }

    /**
     * @dataProvider cacheIntegrationProvider
     */
    public function testDefaultTtl(CacheInterface $cache)
    {
        /** @var Cache $cache */
        $this->assertSame(0, $cache->handler->getDefaultTtl());
    }

    /**
     * @dataProvider cacheIntegrationProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testExpireAdd(CacheInterface $cache)
    {
        $this->assertTrue($cache->add('expire_testa', 'expire_testa', 2));
        usleep(500000);
        $this->assertEquals('expire_testa', $cache->get('expire_testa'));
        usleep(2500000);
        $this->assertNull($cache->get('expire_testa'));
    }

    /**
     * @dataProvider preparedIntegrationCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testAdd(CacheInterface $cache)
    {
        // should not change existing keys
        $this->assertFalse($cache->add('number_test', 13));
        $this->assertEquals(42, $cache->get('number_test'));

        // should store data if it's not there yet
        $this->assertNull($cache->get('add_test'));
        $this->assertTrue($cache->add('add_test', 13));
        $this->assertEquals(13, $cache->get('add_test'));
    }

    /**
     * @dataProvider cacheIntegrationProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testAddMultiple(CacheInterface $cache)
    {
        /** @var CacheInterface $cache */
        $cache = $this->prepare($cache);
        $this->assertNull($cache->get('add_test'));

        $this->assertTrue(
            $cache->addMultiple(
                [
                    'number_test' => 13,
                    'add_test' => 13,
                ]
            )
        );

        $this->assertEquals(42, $cache->get('number_test'));
        $this->assertEquals(13, $cache->get('add_test'));
    }

    /**
     * @dataProvider preparedIntegrationCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGetOrSet(CacheInterface $cache)
    {
        $expected = $this->getOrSetCallable($cache);
        $callable = [$this, 'getOrSetCallable'];

        $this->assertEquals(null, $cache->get('something'));
        $this->assertEquals($expected, $cache->getOrSet('something', $callable));
        $this->assertEquals($expected, $cache->get('something'));
    }

    /**
     * @dataProvider preparedCacheProvider
     */
    public function testGetOrSetWithDependencies(PsrCacheInterface $cache)
    {
        $cache = new Cache($cache);
        $dependency = new TagDependency('test');

        $expected = 'SilverFire';
        $loginClosure = function ($cache) use (&$login) {
            return 'SilverFire';
        };
        $this->assertEquals($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));

        // Call again with another login to make sure that value is cached
        $loginClosure = function ($cache) use (&$login) {
            return 'SamDark';
        };
        $got = $cache->getOrSet('some-login', $loginClosure, null, $dependency);
        $this->assertEquals($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));

        $dependency->invalidate($cache, 'test');
        $expected = 'SamDark';
        $this->assertEquals($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));
    }
}