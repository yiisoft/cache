<?php
namespace Yiisoft\Cache;

use Psr\Log\LoggerInterface;
use Yiisoft\Cache\Exceptions\CacheException;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * FileCache implements a cache handler using files.
 *
 * For each data value being cached, FileCache will store it in a separate file.
 * The cache files are placed under [[cachePath]]. FileCache will perform garbage collection
 * automatically to remove expired cache files.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'cache' => [
 *             '__class' => Yiisoft\Cache\Cache::class,
 *             'handler' => [
 *                 '__class' => Yiisoft\Cache\FileCache::class,
 *                 'cachePath' => Yiisoft\Aliases\Aliases::get('@runtime/cache'),
 *             ],
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * Please refer to [[\Psr\SimpleCache\CacheInterface]] for common cache operations that are supported by FileCache.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
class FileCache extends SimpleCache
{
    /**
     * @var string the directory to store cache files. You may use [path alias](guide:concept-aliases) here.
     */
    private $cachePath;
    /**
     * @var string cache file suffix. Defaults to '.bin'.
     */
    private $cacheFileSuffix = '.bin';
    /**
     * @var int the level of sub-directories to store cache files. Defaults to 1.
     * If the system has huge number of cache files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     */
    private $directoryLevel = 1;
    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * when storing a piece of data in the cache. Defaults to 10, meaning 0.001% chance.
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all.
     */
    private $gcProbability = 10;
    /**
     * @var int the permission to be set for newly created cache files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    private $fileMode;
    /**
     * @var int the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    private $dirMode = 0775;

    private const NEGATIVE_TTL_REPLACEMENT = 31536000; // 1 year

    private $logger;

    public function __construct(string $cachePath, LoggerInterface $logger, SerializerInterface $serializer = null)
    {
        $this->logger = $logger;
        $this->setCachePath($cachePath);
        parent::__construct($serializer);
    }

    /**
     * Sets cache path and ensures it exists.
     * @param string $cachePath
     */
    public function setCachePath(string $cachePath): void
    {
        $this->cachePath = $cachePath;

        if (!$this->createDirectory($this->cachePath, $this->dirMode)) {
            throw new CacheException('Failed to create cache directory "' . $this->cachePath . '"');
        }
    }


    public function hasValue($key): bool
    {
        $cacheFile = $this->getCacheFile($key);

        return @filemtime($cacheFile) > time();
    }

    /**
     * @param string $cacheFileSuffix
     */
    public function setCacheFileSuffix(string $cacheFileSuffix): void
    {
        $this->cacheFileSuffix = $cacheFileSuffix;
    }

    /**
     * @param int $gcProbability
     */
    public function setGcProbability(int $gcProbability): void
    {
        $this->gcProbability = $gcProbability;
    }

    /**
     * @param int $fileMode
     */
    public function setFileMode(int $fileMode): void
    {
        $this->fileMode = $fileMode;
    }

    /**
     * @param int $dirMode
     */
    public function setDirMode(int $dirMode): void
    {
        $this->dirMode = $dirMode;
    }

    protected function getValue($key, $default = null)
    {
        $cacheFile = $this->getCacheFile($key);

        if (@filemtime($cacheFile) > time()) {
            $fp = @fopen($cacheFile, 'rb');
            if ($fp !== false) {
                @flock($fp, LOCK_SH);
                $cacheValue = @stream_get_contents($fp);
                @flock($fp, LOCK_UN);
                @fclose($fp);
                return $cacheValue;
            }
        }

        return $default;
    }

    protected function setValue($key, $value, $ttl): bool
    {
        $this->gc();
        $cacheFile = $this->getCacheFile($key);
        if ($this->directoryLevel > 0) {
            $directoryName = \dirname($cacheFile);
            if (!$this->createDirectory($directoryName, $this->dirMode)) {
                $this->logger->warning('Failed to create cache directory "' . $directoryName . '"');
                return false;
            }
        }
        // If ownership differs the touch call will fail, so we try to
        // rebuild the file from scratch by deleting it first
        // https://github.com/yiisoft/yii2/pull/16120
        if (\function_exists('posix_geteuid') && is_file($cacheFile) && fileowner($cacheFile) !== posix_geteuid()) {
            @unlink($cacheFile);
        }

        if (@file_put_contents($cacheFile, $value, LOCK_EX) !== false) {
            if ($this->fileMode !== null) {
                @chmod($cacheFile, $this->fileMode);
            }
            if ($ttl <= 0) {
                $ttl = self::NEGATIVE_TTL_REPLACEMENT;
            }
            return @touch($cacheFile, $ttl + time());
        }

        $error = error_get_last();
        $this->logger->warning("Failed to write cache data to \"$cacheFile\": " . $error['message']);
        return false;
    }

    protected function deleteValue($key): bool
    {
        $cacheFile = $this->getCacheFile($key);
        return @unlink($cacheFile);
    }

    /**
     * Returns the cache file path given the cache key.
     * @param string $key cache key
     * @return string the cache file path
     */
    protected function getCacheFile(string $key): string
    {
        if ($this->directoryLevel > 0) {
            $base = $this->cachePath;
            for ($i = 0; $i < $this->directoryLevel; ++$i) {
                if (($prefix = substr($key, $i + $i, 2)) !== false) {
                    $base .= DIRECTORY_SEPARATOR . $prefix;
                }
            }

            return $base . DIRECTORY_SEPARATOR . $key . $this->cacheFileSuffix;
        }

        return $this->cachePath . DIRECTORY_SEPARATOR . $key . $this->cacheFileSuffix;
    }

    public function clear(): bool
    {
        $this->removeCacheFiles($this->cachePath, false);
        return true;
    }

    /**
     * Removes expired cache files
     * @throws \Exception
     */
    public function gc(): void
    {
        if (\random_int(0, 1000000) < $this->gcProbability) {
            $this->removeCacheFiles($this->cachePath, true);
        }
    }

    /**
     * Recursively removing expired cache files under a directory.
     * This method is mainly used by [[gc()]].
     * @param string $path the directory under which expired cache files are removed.
     * @param bool $expiredOnly whether to only remove expired cache files. If false, all files
     * under `$path` will be removed.
     */
    protected function removeCacheFiles(string $path, bool $expiredOnly): void
    {
        if (($handle = opendir($path)) !== false) {
            while (($file = readdir($handle)) !== false) {
                if (strpos($file, '.') === 0) {
                    continue;
                }
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fullPath)) {
                    $this->removeCacheFiles($fullPath, $expiredOnly);
                    if (!$expiredOnly && !@rmdir($fullPath)) {
                        $error = error_get_last();
                        throw new CacheException("Unable to remove directory '{$fullPath}': {$error['message']}");
                    }
                } elseif (!$expiredOnly || ($expiredOnly && @filemtime($fullPath) < time())) {
                    if (!@unlink($fullPath)) {
                        $error = error_get_last();
                        throw new CacheException("Unable to remove file '{$fullPath}': {$error['message']}");
                    }
                }
            }
            closedir($handle);
        }
    }

    private function createDirectory(string $cachePath, int $mode, bool $recursive = true): bool
    {
        return is_dir($cachePath) || (mkdir($cachePath, $mode, $recursive) && is_dir($cachePath));
    }
}
