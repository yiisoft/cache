<?php
namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\Dependencies\Dependency;
use Yiisoft\Cache\NullCache;

/**
 * Dependency (abstract) tests.
 * @group caching
 */
class DependencyTest extends TestCase
{
    public function testResetReusableData(): void
    {
        $value = ['dummy'];
        $dependency = new MockDependency();
        $this->setInaccessibleProperty($dependency, 'reusableData', $value, false);
        $this->assertEquals($value, $this->getInaccessibleProperty($dependency, 'reusableData'));

        $dependency->resetReusableData();

        $this->assertEquals([], $this->getInaccessibleProperty($dependency, 'reusableData'));
    }

    public function testGenerateReusableHash(): void
    {
        $dependency = $this->getMockForAbstractClass(Dependency::class);
        $dependency->data = 'dummy';

        $result = $this->invokeMethod($dependency, 'generateReusableHash');
        $this->assertEquals(5, strlen($dependency->data));
        $this->assertEquals(40, strlen($result));
    }

    public function testIsChanged(): void
    {
        /* @var $dependency Dependency */

        $dependency = $this->getMockForAbstractClass(Dependency::class);
        $cache = new NullCache();

        $result = $dependency->isChanged($cache);
        $this->assertFalse($result);

        $dependency->data = 'changed';
        $result = $dependency->isChanged($cache);
        $this->assertTrue($result);
    }
}
