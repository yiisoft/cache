<?php
namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\Dependency\Dependency;

/**
 * Dependency (abstract) tests.
 * @group caching
 */
class DependencyTest extends DependencyTestCase
{
    public function testResetReusableData(): void
    {
        $value = ['dummy'];
        $dependency = new MockDependency();
        $this->setInaccessibleProperty($dependency, 'reusableData', $value, false);
        $this->assertSameExceptObject($value, $this->getInaccessibleProperty($dependency, 'reusableData'));

        $dependency->resetReusableData();

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

    public function testIsChanged(): void
    {
        /* @var $dependency Dependency */
        $dependency = $this->getMockForAbstractClass(Dependency::class);
        $this->assertDependencyNotChanged($dependency);

        $this->setInaccessibleProperty($dependency, 'data', 'changed');

        $this->assertDependencyChanged($dependency);
    }
}
