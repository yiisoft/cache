<?php

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Exception;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Exception\CacheException;
use Yiisoft\Cache\Serializer\PhpSerializer;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * FileCache implements a cache handler using files.
 *
 * For each data value being cached, FileCache will store it in a separate file.
 * The cache files are placed under {@see FileCache::$cachePath}. FileCache will perform garbage collection
 * automatically to remove expired cache files.
 *
 * Please refer to {@see \Psr\SimpleCache\CacheInterface} for common cache operations that are supported by FileCache.
 */
final class FileCache implements CacheInterface
{
    private const TTL_INFINITY = 31536000; // 1 year
    private const EXPIRATION_EXPIRED = -1;

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

    /**
     * @var SerializerInterface the serializer to be used for serializing and unserializing of the cached data.
     */
    private $serializer;

    public function __construct(string $cachePath, ?SerializerInterface $serializer = null)
    {
        $this->cachePath = $cachePath;
        $this->serializer = $serializer ?? new PhpSerializer();
        $this->initCacheDirectory();
    }

    public function get($key, $default = null)
    {
        if ($this->existsAndNotExpired($key)) {
            $fp = @fopen($this->getCacheFile($key), 'rb');
            if ($fp !== false) {
                @flock($fp, LOCK_SH);
                $cacheValue = @stream_get_contents($fp);
                @flock($fp, LOCK_UN);
                @fclose($fp);
                $cacheValue = $this->serializer->unserialize($cacheValue);
                return $cacheValue;
            }
        }

        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->gc();

        $expiration = $this->ttlToExpiration($ttl);
        if ($expiration < 0) {
            return $this->delete($key);
        }

        $cacheFile = $this->getCacheFile($key);
        if ($this->directoryLevel > 0) {
            $directoryName = \dirname($cacheFile);
            if (!$this->createDirectory($directoryName, $this->dirMode)) {
                return false;
            }
        }
        // If ownership differs the touch call will fail, so we try to
        // rebuild the file from scratch by deleting it first
        // https://github.com/yiisoft/yii2/pull/16120
        if (\function_exists('posix_geteuid') && is_file($cacheFile) && fileowner($cacheFile) !== posix_geteuid()) {
            @unlink($cacheFile);
        }

        $value = $this->serializer->serialize($value);

        if (@file_put_contents($cacheFile, $value, LOCK_EX) !== false) {
            if ($this->fileMode !== null) {
                @chmod($cacheFile, $this->fileMode);
            }
            return @touch($cacheFile, $expiration);
        }

        return false;
    }

    public function delete($key)
    {
        return @unlink($this->getCacheFile($key));
    }

    public function clear()
    {
        $this->removeCacheFiles($this->cachePath, false);
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        $results = [];
        foreach ($keys as $key) {
            $value = $this->get($key, $default);
            $results[$key] = $value;
        }
        return $results;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key)
    {
        return $this->existsAndNotExpired($key);
    }

    /**
     * Converts TTL to expiration
     * @param $ttl
     * @return int
     */
    private function ttlToExpiration($ttl): int
    {
        $ttl = $this->normalizeTtl($ttl);

        if ($ttl === null) {
            $expiration = static::TTL_INFINITY + time();
        } elseif ($ttl <= 0) {
            $expiration = static::EXPIRATION_EXPIRED;
        } else {
            $expiration = $ttl + time();
        }

        return $expiration;
    }

    /**
     * Normalizes cache TTL handling `null` value and {@see DateInterval} objects.
     * @param int|DateInterval|null $ttl raw TTL.
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     */
    private function normalizeTtl($ttl): ?int
    {
        if ($ttl instanceof DateInterval) {
            try {
                return (new DateTime('@0'))->add($ttl)->getTimestamp();
            } catch (Exception $e) {
                return null;
            }
        }

        return $ttl;
    }

    /**
     * Ensures that cache directory exists.
     */
    private function initCacheDirectory(): void
    {
        if (!$this->createDirectory($this->cachePath, $this->dirMode)) {
            throw new CacheException('Failed to create cache directory "' . $this->cachePath . '"');
        }
    }

    private function createDirectory(string $path, int $mode): bool
    {
        return is_dir($path) || (mkdir($path, $mode, true) && is_dir($path));
    }

    /**
     * Returns the cache file path given the cache key.
     * @param string $key cache key
     * @return string the cache file path
     */
    private function getCacheFile(string $key): string
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
     * This method is mainly used by {@see gc()}.
     * @param string $path the directory under which expired cache files are removed.
     * @param bool $expiredOnly whether to only remove expired cache files. If false, all files
     * under `$path` will be removed.
     */
    private function removeCacheFiles(string $path, bool $expiredOnly): void
    {
        if (($handle = opendir($path)) !== false) {
            while (($file = readdir($handle)) !== false) {
                if (strncmp($file, '.', 1) === 0) {
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

    /**
     * @param string $cacheFileSuffix cache file suffix. Defaults to '.bin'.
     */
    public function setCacheFileSuffix(string $cacheFileSuffix): void
    {
        $this->cacheFileSuffix = $cacheFileSuffix;
    }

    /**
     * @param int $gcProbability the probability (parts per million) that garbage collection (GC) should be performed
     * when storing a piece of data in the cache. Defaults to 10, meaning 0.001% chance.
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all.
     */
    public function setGcProbability(int $gcProbability): void
    {
        $this->gcProbability = $gcProbability;
    }

    /**
     * @param int $fileMode the permission to be set for newly created cache files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    public function setFileMode(int $fileMode): void
    {
        $this->fileMode = $fileMode;
    }

    /**
     * @param int $dirMode the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public function setDirMode(int $dirMode): void
    {
        $this->dirMode = $dirMode;
    }

    /**
     * @param int $directoryLevel the level of sub-directories to store cache files. Defaults to 1.
     * If the system has huge number of cache files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     *
     */
    public function setDirectoryLevel(int $directoryLevel): void
    {
        $this->directoryLevel = $directoryLevel;
    }

    private function existsAndNotExpired(string $key)
    {
        return @filemtime($this->getCacheFile($key)) > time();
    }
}
