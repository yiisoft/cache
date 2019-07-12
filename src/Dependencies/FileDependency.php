<?php
namespace Yiisoft\Cache\Dependencies;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Exceptions\InvalidConfigException;

/**
 * FileDependency represents a dependency based on a file's last modification time.
 *
 * If the last modification time of the file specified via {@see fileName} is changed,
 * the dependency is considered as changed.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
final class FileDependency extends Dependency
{
    private $fileName;

    /**
     * @param string $fileName the file path or [path alias](guide:concept-aliases) whose last modification time is used to
     * check if the dependency has been changed.
     */
    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Generates the data needed to determine if dependency has been changed.
     * This method returns the file's last modification time.
     * @param CacheInterface $cache the cache component that is currently evaluating this dependency
     * @return mixed the data needed to determine if dependency has been changed.
     * @throws InvalidConfigException if {@see fileName} is not set
     */
    protected function generateDependencyData(CacheInterface $cache)
    {
        if ($this->fileName === null) {
            throw new InvalidConfigException('FileDependency::fileName must be set');
        }

        clearstatcache(false, $this->fileName);
        return @filemtime($this->fileName);
    }
}
