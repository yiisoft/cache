<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache;

use yii\helpers\FileHelper;
use yii\helpers\Yii;
use Yiisoft\Cache\Exceptions\Exception;
use Yiisoft\Cache\Serializer\SerializerInterface;
use Yiisoft\Strings\StringHelper;

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

    private const NEGATIVE_TTL_REPLACEMENT = 60 * 60 * 24 * 365;


    public function __construct(string $cachePath = '@runtime/cache', SerializerInterface $serializer = null)
    {
        $this->setCachePath($cachePath);
        parent::__construct($serializer);
    }

    /**
     * Sets cache path and ensures it exists.
     * @param string $cachePath
     */
    public function setCachePath(string $cachePath)
    {
        $this->cachePath = $cachePath;
        if (!is_dir($this->cachePath)) {
            FileHelper::createDirectory($this->cachePath, $this->dirMode, true);
        }
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
     */
    protected function setValue($key, $value, $ttl): bool
    {
        $this->gc();
        $cacheFile = $this->getCacheFile($key);
        if ($this->directoryLevel > 0) {
            try {
                @FileHelper::createDirectory(\dirname($cacheFile), $this->dirMode, true);
            } catch (\yii\exceptions\Exception $error) {
                return false;
            }
        }
        // If ownership differs the touch call will fail, so we try to
        // rebuild the file from scratch by deleting it first
        // https://github.com/yiisoft/yii2/pull/16120
        if (is_file($cacheFile) && \function_exists('posix_geteuid') && fileowner($cacheFile) !== posix_geteuid()) {
            @unlink($cacheFile);
        }

        try {
            // this implementation is more accurate than file_put_contents
            // because it does not modify file content, if failed to read
            if ($fd = fopen($cacheFile, 'cb'))
            {
                if (!flock($fd, LOCK_EX)) {
                    throw new Exception("Failed to flock '$cacheFile'");
                } else if(!ftruncate($fd, 0)) {
                    throw new Exception("Failed to truncate '$cacheFile");
                } else if(file_put_contents($cacheFile, $value) !== StringHelper::byteLength($value)) {
                    throw new Exception("Failed to write data to '$cacheFile' totally");
                }
                if ($ttl <= 0) {
                    $ttl = self::NEGATIVE_TTL_REPLACEMENT;
                }
                $mtimeInstallationResult = touch($cacheFile, time() + $ttl);
                if (!$mtimeInstallationResult) {
                    throw new Exception("Failed to install mtime to file '$cacheFile'");
                }
                flock($fd, LOCK_UN);
                fclose($fd);
                return true;
            }
        } catch (Exception $error) {
            unlink($cacheFile);
        }
        return false;
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
     * @param string $key cache key
     * @return string the cache file path
     */
    protected function getCacheFile($key)
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
     */
    public function clear(): bool
    {
        $this->gc(true, false);
        return true;
    }

    /**
     * Removes expired cache files.
     * @param bool $force whether to enforce the garbage collection regardless of [[gcProbability]].
     * Defaults to false, meaning the actual deletion happens with the probability as specified by [[gcProbability]].
     * @param bool $expiredOnly whether to removed expired cache files only.
     * If false, all cache files under [[cachePath]] will be removed.
     */
    public function gc($force = false, $expiredOnly = true)
    {
        if ($force || random_int(0, 1000000) < $this->gcProbability) {
            $this->gcRecursive($this->cachePath, $expiredOnly);
        }
    }

    /**
     * Recursively removing expired cache files under a directory.
     * This method is mainly used by [[gc()]].
     * @param string $path the directory under which expired cache files are removed.
     * @param bool $expiredOnly whether to only remove expired cache files. If false, all files
     * under `$path` will be removed.
     */
    protected function gcRecursive($path, $expiredOnly)
    {
        if (($handle = opendir($path)) !== false) {
            while (($file = readdir($handle)) !== false) {
                if ($file[0] === '.') {
                    continue;
                }
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fullPath)) {
                    $this->gcRecursive($fullPath, $expiredOnly);
                    if (!$expiredOnly && !@rmdir($fullPath)) {
                        $error = error_get_last();
                        Yii::warning("Unable to remove directory '{$fullPath}': {$error['message']}", __METHOD__);
                    }
                } elseif (!$expiredOnly || ($expiredOnly && @filemtime($fullPath) < time())) {
                    if (!@unlink($fullPath)) {
                        $error = error_get_last();
                        Yii::warning("Unable to remove file '{$fullPath}': {$error['message']}", __METHOD__);
                    }
                }
            }
            closedir($handle);
        }
    }
}
