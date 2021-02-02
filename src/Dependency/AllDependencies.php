<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Dependency;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Exception\InvalidArgumentException;

use function gettype;
use function get_class;
use function is_object;
use function sprintf;

/**
 * AllDependencies represents a dependency which is composed of a list of other dependencies.
 *
 * The dependency is reported as changed if all sub-dependencies are changed.
 */
final class AllDependencies extends Dependency
{
    /**
     * @var Dependency[]
     */
    private array $dependencies;

    /**
     * @param array $dependencies list of dependencies that this dependency is composed of.
     * Each array element must be a dependency object.
     */
    public function __construct(array $dependencies = [])
    {
        foreach ($dependencies as $dependency) {
            if (!($dependency instanceof Dependency)) {
                throw new InvalidArgumentException(sprintf(
                    'The dependency must be a "%s" instance, "%s" received',
                    Dependency::class,
                    is_object($dependency) ? get_class($dependency) : gettype($dependency),
                ));
            }
        }

        $this->dependencies = $dependencies;
    }

    public function evaluateDependency(CacheInterface $cache): void
    {
        foreach ($this->dependencies as $dependency) {
            $dependency->evaluateDependency($cache);
        }
    }

    /**
     * @codeCoverageIgnore Method is not used.
     *
     * @param CacheInterface $cache
     *
     * @return mixed
     */
    protected function generateDependencyData(CacheInterface $cache)
    {
        return null;
    }

    public function isChanged(CacheInterface $cache): bool
    {
        foreach ($this->dependencies as $dependency) {
            if (!$dependency->isChanged($cache)) {
                return false;
            }
        }

        return true;
    }
}
