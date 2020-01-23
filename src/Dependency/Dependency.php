<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Dependency;

use Yiisoft\Cache\CacheInterface;

/**
 * Dependency is the base class for cache dependency classes.
 *
 * Child classes should override its {@see Dependency::generateDependencyData()} for generating
 * the actual dependency data.
 */
abstract class Dependency
{
    /**
     * @var mixed the dependency data that is saved in cache and later is compared with the
     * latest dependency data
     */
    protected $data;

    /**
     * @var bool whether this dependency is reusable or not. True value means that dependent
     * data for this cache dependency will be generated only once per request. This allows you
     * to use the same cache dependency for multiple separate cache calls while generating the same
     * page without an overhead of re-evaluating dependency data each time. Defaults to false.
     */
    protected $isReusable = false;

    /**
     * @var array static storage of cached data for reusable dependencies.
     */
    private static $reusableData = [];

    /**
     * Changes dependency behavior so dependent data for this cache dependency will be generated only once per request.
     * This allows you to use the same cache dependency for multiple separate cache calls while generating the same
     * page without an overhead of re-evaluating dependency data each time.
     */
    public function markAsReusable(): void
    {
        $this->isReusable = true;
    }

    /**
     * Evaluates the dependency by generating and saving the data related with dependency.
     * This method is invoked by cache before writing data into it.
     * @param CacheInterface $cache the cache component that is currently evaluating this dependency
     */
    public function evaluateDependency(CacheInterface $cache): void
    {
        if ($this->isReusable) {
            $hash = $this->generateReusableHash();
            if (!\array_key_exists($hash, self::$reusableData)) {
                self::$reusableData[$hash] = $this->generateDependencyData($cache);
            }
            $this->data = self::$reusableData[$hash];
        } else {
            $this->data = $this->generateDependencyData($cache);
        }
    }

    /**
     * Checks whether the dependency is changed.
     * @param CacheInterface $cache the cache component that is currently evaluating this dependency
     * @return bool whether the dependency has changed.
     */
    public function isChanged(CacheInterface $cache): bool
    {
        if ($this->isReusable) {
            $hash = $this->generateReusableHash();
            if (!\array_key_exists($hash, self::$reusableData)) {
                self::$reusableData[$hash] = $this->generateDependencyData($cache);
            }
            $data = self::$reusableData[$hash];
        } else {
            $data = $this->generateDependencyData($cache);
        }

        return $data !== $this->data;
    }

    /**
     * Resets all cached data for reusable dependencies.
     */
    public static function resetReusableData(): void
    {
        self::$reusableData = [];
    }

    /**
     * Generates a unique hash that can be used for retrieving reusable dependency data.
     * @return string a unique hash value for this cache dependency.
     * @see isReusable
     */
    protected function generateReusableHash(): string
    {
        $data = $this->data;
        $this->data = null;  // https://github.com/yiisoft/yii2/issues/3052
        $key = sha1(serialize($this));
        $this->data = $data;
        return $key;
    }

    /**
     * Converts iterable to array
     * @param iterable $iterable
     * @return array
     */
    protected function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable) : (array)$iterable;
    }

    /**
     * Generates the data needed to determine if dependency is changed.
     * Derived classes should override this method to generate the actual dependency data.
     * @param CacheInterface $cache the cache component that is currently evaluating this dependency
     * @return mixed the data needed to determine if dependency has been changed.
     */
    abstract protected function generateDependencyData(CacheInterface $cache);
}
