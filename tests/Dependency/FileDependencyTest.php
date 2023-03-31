<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\FileDependency;

use function dirname;

final class FileDependencyTest extends DependencyTestCase
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
        $this->createDirectory(dirname($this->getFilePath()), 0775);
        touch($this->getFilePath());
    }

    public function testTouchingFileMarksDependencyAsChanged(): void
    {
        $this->touchFile();
        $dependency = $this->createDependency();
        $this->touchFile();

        $this->assertDependencyChanged($dependency);
    }

    public function testDependencyIsChangedReusable(): void
    {
        $cache = new Cache(new ArrayCache());
        $this->touchFile();
        $dependency = $this->createDependency();
        $dependency->markAsReusable();
        $cache->getOrSet('a', static fn () => 1, null, $dependency);
        $cache->getOrSet('b', static fn () => 2, null, $dependency);
        $this->assertSame(1, $cache->getOrSet('a', static fn () => null));
        sleep(1);
        $this->touchFile();
        $this->assertSame(2, $cache->getOrSet('b', static fn () => null));
    }

    private function createDirectory(string $path, int $mode): bool
    {
        return is_dir($path) || (mkdir($path, $mode, true) && is_dir($path));
    }
}
