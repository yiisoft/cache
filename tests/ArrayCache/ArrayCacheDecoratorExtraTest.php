<?php

namespace Yiisoft\Cache\Tests\ArrayCache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\Exception\SetCacheException;
use Yiisoft\Cache\Tests\FalseCache;
use Yiisoft\Cache\Tests\MockHelper;
use Yiisoft\Cache\Tests\TestCase;

class ArrayCacheDecoratorExtraTest extends TestCase
{
    protected function tearDown(): void
    {
        MockHelper::resetMocks();
    }

    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new ArrayCache());
    }

    public function testAdd(): void
    {
        /** @var Cache $cache */
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        // should not change existing keys
        $this->assertSameExceptObject('a', $cache->get('test_string'));
        $this->assertFalse($cache->add('test_string', 'b'));


        // should store data if it's not there yet
        $this->assertNull($cache->get('add_test'));
        $this->assertTrue($cache->add('add_test', 13));
        $this->assertSameExceptObject(13, $cache->get('add_test'));
    }

    public function testAddWithDependency(): void
    {
        /** @var Cache $cache */
        $cache = $this->createCacheInstance();

        $dependency = new TagDependency('tag_test');

        $cache->add('a', 1, null, $dependency);

        $this->assertSameExceptObject(1, $cache->get('a'));

        TagDependency::invalidate($cache, 'tag_test');

        $this->assertSameExceptObject(null, $cache->get('a'));
    }

    public function testSetMultipleWithDependency(): void
    {
        /** @var Cache $cache */
        $cache = $this->createCacheInstance();

        $dependency = new TagDependency('tag_test');

        $cache->setMultiple(['a' => 1, 'b' => 2], null, $dependency);

        $this->assertSameExceptObject(['a' => 1, 'b' => 2], $cache->getMultiple(['a', 'b']));

        TagDependency::invalidate($cache, 'tag_test');

        $this->assertSameExceptObject(['a' => null, 'b' => null], $cache->getMultiple(['a', 'b']));
    }

    public function testAddMultiple(): void
    {
        /** @var Cache $cache */
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        $this->assertNull($cache->get('add_test'));

        $this->assertTrue(
            $cache->addMultiple(
                [
                    'test_integer' => 13,
                    'add_test' => 13,
                    '444' => 14,
                ]
            )
        );

        $this->assertSameExceptObject(1, $cache->get('test_integer'));
        $this->assertSameExceptObject(13, $cache->get('add_test'));
        $this->assertSameExceptObject(14, $cache->get('444'));
    }

    public function testGetOrSet(): void
    {
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        $expected = get_class($cache);

        $this->assertSameExceptObject(null, $cache->get('something'));
        $this->assertSameExceptObject($expected, $cache->getOrSet('something', static function (CacheInterface $cache): string {
            return get_class($cache);
        }));
        $this->assertSameExceptObject($expected, $cache->get('something'));
    }

    public function testGetOrSetException(): void
    {
        $this->expectException(SetCacheException::class);

        $cache = new Cache(new FalseCache());

        $cache->getOrSet('something', static function (CacheInterface $cache): string {
            return get_class($cache);
        });
    }

    public function testSetCacheException(): void
    {
        $cache = new Cache(new FalseCache());

        try {
            $cache->getOrSet('something', static function (CacheInterface $cache): string {
                return get_class($cache);
            });
        } catch (SetCacheException $e) {
            $this->assertEquals('something', $e->getKey());
            $this->assertEquals('Yiisoft\Cache\Cache', $e->getValue());
            $this->assertEquals($cache, $e->getCache());
        }
    }

    public function testGetOrSetWithDependencies(): void
    {
        /** @var Cache $cache */
        $cache = $this->createCacheInstance();

        $dependency = new TagDependency('test');

        $expected = 'SilverFire';
        $loginClosure = static function (): string {
            return 'SilverFire';
        };
        $this->assertSameExceptObject($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));

        // Call again with another login to make sure that value is cached
        $loginClosure = static function (): string {
            return 'SamDark';
        };
        $cache->getOrSet('some-login', $loginClosure, null, $dependency);
        $this->assertSameExceptObject($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));

        TagDependency::invalidate($cache, 'test');
        $expected = 'SamDark';
        $this->assertSameExceptObject($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));
    }

    public function testWithArrayKeys(): void
    {
        $key = [42];
        $cache = $this->createCacheInstance();
        $cache->clear();

        $this->assertNull($cache->get($key));

        $cache->set($key, 42);
        $this->assertSame(42, $cache->get($key));
    }

    public function testInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MockHelper::$mock_json_encode = false;
        $key = [42];
        $cache = $this->createCacheInstance();
        $cache->clear();
        $cache->set($key, 42);
    }

    public function testWithObjectKeys(): void
    {
        $key = new class {
            public $value = 42;
        };
        $cache = $this->createCacheInstance();
        $cache->clear();

        $this->assertNull($cache->get($key));

        $cache->set($key, 42);
        $this->assertSame(42, $cache->get($key));
    }

    public function testNormalizeKey(): void
    {
        /** @var Cache $cache */
        $cache = $this->createCacheInstance();
        $cache->clear();
        $cache->enableKeyNormalization();

        $cache->set('test_normalized', 1);

        $cache->disableKeyNormalization();

        $cache->set('test_not_normalized', 2);

        $cache->disableKeyNormalization();
        $this->assertFalse($cache->has('test_normalized'));
        $this->assertSameExceptObject(1, $cache->get('2753a58fdc3bf713af86cb3a97a55e57'));
        $this->assertSameExceptObject(2, $cache->get('test_not_normalized'));
    }

    public function testGetWithPrefix(): void
    {
        /** @var Cache $cache */
        $cache = $this->createCacheInstance();
        $cache->setKeyPrefix('prefix');
        $cache = $this->prepare($cache);
        $this->assertSameExceptObject(1, $cache->get('test_integer'));
    }

    public function testInvalidKeyPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var Cache $cache */
        $cache = $this->createCacheInstance();
        $cache->setKeyPrefix('_prefix');
    }

    public function testKeyPrefix(): void
    {
        /** @var Cache $cache */
        $cache = $this->createCacheInstance();
        $cache->clear();
        $cache->disableKeyNormalization();
        $cache->setKeyPrefix('prefix');

        $cache->set('test_with_prefix', 1);

        $cache->setKeyPrefix('');

        $cache->set('test_without_prefix', 2);

        $cache->setKeyPrefix('');
        $this->assertFalse($cache->has('test_with_prefix'));
        $this->assertSameExceptObject(1, $cache->get('prefixtest_with_prefix'));
        $this->assertSameExceptObject(2, $cache->get('test_without_prefix'));
    }

    /**
     * @dataProvider featuresProvider
     * @param $features
     */
    public function testFeatures($features): void
    {
        /** @var Cache $cache */
        $cache = $this->createCacheInstance();
        $cache->setKeyPrefix($features[0]);
        $features[1] ? $cache->enableKeyNormalization() : $cache->disableKeyNormalization();

        $this->featuresTest($cache);
    }

    public function featuresProvider(): array
    {
        // [prefix, normalization]
        return [
            [['', false]],
            [['testprefix', false]],
            [['testprefix', true]],
            [['', true]],
        ];
    }

    private function featuresTest(Cache $cache): void
    {
        $this->prepare($cache);

        $dataWithPrefix = $this->getDataProviderData('for_multiple_');
        $cache->setMultiple($dataWithPrefix);

        $data = $this->getDataProviderData() + $dataWithPrefix + ['nonexistent-key' => null];
        $keys = array_map('strval', array_keys($data));
        $dataWithDefault = $data;
        $dataWithDefault['nonexistent-key'] = 'default';

        foreach ($data as $key => $value) {
            $key = (string)$key;
            if ($key === 'nonexistent-key') {
                $this->assertFalse($cache->has($key));
                $this->assertSameExceptObject(null, $cache->get($key));
                $this->assertSameExceptObject('default', $cache->get($key, 'default'));
            } else {
                $this->assertTrue($cache->has($key));
                $this->assertSameExceptObject($value, $cache->get($key));
                $this->assertSameExceptObject($value, $cache->get($key, 'default'));
            }
        }

        $this->assertSameExceptObject($data, $cache->getMultiple($keys));
        $this->assertSameExceptObject($dataWithDefault, $cache->getMultiple($keys, 'default'));
    }

    public function testDefaultTtl(): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();
        /** @var Cache $cache */
        $cache->setDefaultTtl(2);
        $this->assertSameExceptObject(2, $cache->getDefaultTtl());
    }

    public function testDateIntervalTtl(): void
    {
        $interval = new DateInterval('PT3S');
        $cache = $this->createCacheInstance();
        $cache->clear();
        /** @var Cache $cache */
        $cache->setDefaultTtl($interval);
        $this->assertSameExceptObject(3, $cache->getDefaultTtl());
    }
}
