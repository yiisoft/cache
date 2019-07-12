<?php
namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\Dependency\FileDependency;

class FileDependencyTest extends DependencyTestCase
{
    private function getFilePath(): string
    {
        return dirname(__DIR__) . '/runtime/file.txt';
    }

    private function createDependency(): FileDependency
    {
        return new FileDependency($this->getFilePath());
    }

    private function touchFile(): void
    {
        touch($this->getFilePath());
    }

    public function testTouchingFileMarksDependencyAsChanged(): void
    {
        $this->touchFile();
        $dependency = $this->createDependency();
        $this->touchFile();

        $this->assertDependencyChanged($dependency);
    }
}
