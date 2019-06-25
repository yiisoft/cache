<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache;

use Yiisoft\Cache\Exceptions\Exception;
use Yiisoft\Cache\Exceptions\SetCacheException;
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
 *                 // 'cachePath' => '@runtime/cache',
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
     * If not set, it will use the "cache" subdirectory under the application runtime path.
     */
    public $_cachePath;
    /**
     * @var string cache file suffix. Defaults to '.bin'.
     */
    public $cacheFileSuffix = '.bin';
    /**
     * @var int the level of sub-directories to store cache files. Defaults to 1.
     * If the system has huge number of cache files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     */
    public $directoryLevel = 1;
    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * when storing a piece of data in the cache. Defaults to 10, meaning 0.001% chance.
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all.
     */
    public $gcProbability = 10;
    /**
     * @var int the permission to be set for newly created cache files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    public $fileMode;
    /**
     * @var int the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public $dirMode = 0775;


    /**
     * @param string $cachePath
     * @param SerializerInterface   $serializer
     *
     * @throws Exception
     */
    public function __construct(string $cachePath = '@runtime/cache', SerializerInterface $serializer = null)
    {
        $this->setCachePath($cachePath);
        parent::__construct($serializer);
    }

    /**
     * Sets cache path and ensures it exists.
     *
     * @param string $cachePath
     *
     * @throws Exception
     */
    public function setCachePath(string $cachePath)
    {
        $this->cachePath = $cachePath;

        $this->createDirectory($this->cachePath, $this->dirMode);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key): bool
    {
        $cacheFile = $this->getCacheFile($this->normalizeKey($key));

        return @filemtime($cacheFile) > time();
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue($key)
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

        return false;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    protected function setValue($key, $value, $ttl): bool
    {
        $this->gc();
        $cacheFile = $this->getCacheFile($key);
        if ($this->directoryLevel > 0) {
            $this->createDirectory(\dirname($cacheFile), $this->dirMode);
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
                $ttl = 31536000; // 1 year
            }

            return @touch($cacheFile, $ttl + time());
        }

        $error = error_get_last();

        throw new SetCacheException($key, $value, $this, "Unable to write cache file '{$cacheFile}': {$error['message']}");
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteValue($key): bool
    {
        $cacheFile = $this->getCacheFile($key);
        return @unlink($cacheFile);
    }

    /**
     * Returns the cache file path given the cache key.
     *
     * @param string $key cache key
     *
     * @return string the cache file path
     */
    protected function getCacheFile($key): string
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

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function clear(): bool
    {
        $this->gc(true, false);
        return true;
    }

    /**
     * Removes expired cache files.
     *
     * @param bool $force       whether to enforce the garbage collection regardless of [[gcProbability]].
     *                          Defaults to false, meaning the actual deletion happens with the probability as
     *                          specified by [[gcProbability]].
     * @param bool $expiredOnly whether to removed expired cache files only.
     *                          If false, all cache files under [[cachePath]] will be removed.
     *
     * @throws \Exception
     */
    public function gc($force = false, $expiredOnly = true): void
    {
        if ($force || random_int(0, 1000000) < $this->gcProbability) {
            $this->gcRecursive($this->cachePath, $expiredOnly);
        }
    }

    /**
     * Recursively removing expired cache files under a directory.
     * This method is mainly used by [[gc()]].
     *
     * @param string $path        the directory under which expired cache files are removed.
     * @param bool   $expiredOnly whether to only remove expired cache files. If false, all files
     *                            under `$path` will be removed.
     *
     * @throws Exception
     */
    protected function gcRecursive($path, $expiredOnly): void
    {
        if (($handle = opendir($path)) !== false) {
            while (($file = readdir($handle)) !== false) {
                if (strpos($file, '.') === 0) {
                    continue;
                }
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fullPath)) {
                    $this->gcRecursive($fullPath, $expiredOnly);
                    if (!$expiredOnly && !@rmdir($fullPath)) {
                        $error = error_get_last();
                        throw new Exception("Unable to remove directory '{$fullPath}': {$error['message']}");
                    }
                } elseif (!$expiredOnly || ($expiredOnly && @filemtime($fullPath) < time())) {
                    if (!@unlink($fullPath)) {
                        $error = error_get_last();
                        throw new Exception("Unable to remove file '{$fullPath}': {$error['message']}");
                    }
                }
            }
            closedir($handle);
        }
    }

    /**
     * Directory creation
     * See for details in
     * https://github.com/kalessil/phpinspectionsea/blob/master/docs/probable-bugs.md#mkdir-race-condition
     *
     * @param string $cachePath
     * @param int    $mode
     * @param bool   $recursive
     *
     * @throws Exception
     */
    protected function createDirectory(string $cachePath, int $mode, bool $recursive = true): void
    {
        if (!is_dir($cachePath) && !mkdir($cachePath, $mode, $recursive) && !is_dir($cachePath)) {
            throw new Exception("Can't create cache directory $cachePath");
        }
    }
}
