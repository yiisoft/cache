<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Dependency\TagDependency;

use function strlen;

/**
 * Dependency (abstract) tests.
 *
 * @group caching
 */
final class DependencyTest extends DependencyTestCase
{
    public function testResetReusableData(): void
    {
        $value = ['dummy'];
        $dependency = $this->createMockDependency();
        $this->setInaccessibleProperty($dependency, 'reusableData', $value, false);
        $this->assertSameExceptObject($value, $this->getInaccessibleProperty($dependency, 'reusableData'));

        $dependency::resetReusableData();

        $this->assertSameExceptObject([], $this->getInaccessibleProperty($dependency, 'reusableData'));
    }

    public function testGenerateReusableHash(): void
    {
        $dependency = $this->getMockForAbstractClass(Dependency::class);
        $this->setInaccessibleProperty($dependency, 'data', 'dummy');

        $result = $this->invokeMethod($dependency, 'generateReusableHash');

        $this->assertSameExceptObject(5, strlen($this->getInaccessibleProperty($dependency, 'data')));
        $this->assertSameExceptObject(40, strlen($result));
    }

    public function testIsChangedReusable(): void
    {
        /** @var Dependency $dependency */
        $dependency = $this->getMockForAbstractClass(Dependency::class);
        $dependency->markAsReusable();
        $this->assertDependencyNotChanged($dependency);

        $this->setInaccessibleProperty($dependency, 'data', 'changed');

        $this->assertDependencyChanged($dependency);
    }

    public function testIsChanged(): void
    {
        /** @var Dependency $dependency */
        $dependency = $this->getMockForAbstractClass(Dependency::class);
        $this->assertDependencyNotChanged($dependency);

        $this->setInaccessibleProperty($dependency, 'data', 'changed');

        $this->assertDependencyChanged($dependency);
    }

    public function testEvaluateDependencyReusable(): void
    {
        $cache = new Cache(new ArrayCache());
        $dependency = new TagDependency('test');
        $dependency->markAsReusable();
        $cache->getOrSet('a', static fn() => 1, null, $dependency);
        TagDependency::invalidate($cache, 'test');
        $data1 = $this->getInaccessibleProperty($dependency, 'data');
        $cache->getOrSet('b', static fn() => 2, null, $dependency);
        $data2 = $this->getInaccessibleProperty($dependency, 'data');
        $this->assertEquals($data1, $data2);
    }
}
