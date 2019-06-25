<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache;

use Yiisoft\Db\ConnectionInterface;
use Yiisoft\Db\Connection;
use Yiisoft\Db\PdoValue;
use Yiisoft\Db\Query;

/**
 * DbCache implements a cache application component by storing cached data in a database.
 *
 * By default, DbCache stores session data in a DB table named 'cache'. This table
 * must be pre-created. The table name can be changed by setting [[cacheTable]].
 *
 * Please refer to [[\Psr\SimpleCache\CacheInterface]] for common cache operations that are supported by DbCache.
 *
 * The following example shows how you can configure the application to use DbCache:
 *
 * ```php
 * return [
 *     'components' => [
 *         'cache' => [
 *             '__class' => Yiisoft\Cache\Cache:class,
 *             'handler' => [
 *                 '__class' => Yiisoft\Cache\DbCache::class,
 *                 // 'db' => 'mydb',
 *                 // 'cacheTable' => 'my_cache',
 *             ],
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
class DbCache extends SimpleCache
{
    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * After the DbCache object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $db = 'db';
    /**
     * @var string name of the DB table to store cache content.
     * The table should be pre-created as follows:
     *
     * ```php
     * CREATE TABLE cache (
     *     id char(128) NOT NULL PRIMARY KEY,
     *     expire int(11),
     *     data BLOB
     * );
     * ```
     *
     * where 'BLOB' refers to the BLOB-type of your preferred DBMS. Below are the BLOB type
     * that can be used for some popular DBMS:
     *
     * - MySQL: LONGBLOB
     * - PostgreSQL: BYTEA
     * - MSSQL: BLOB
     *
     * When using DbCache in a production server, we recommend you create a DB index for the 'expire'
     * column in the cache table to improve the performance.
     */
    public $cacheTable = '{{%cache}}';
    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * when storing a piece of data in the cache. Defaults to 100, meaning 0.01% chance.
     * This number should be between 0 and 1000000. A value 0 meaning no GC will be performed at all.
     */
    public $gcProbability = 100;

    public function __construct($serializer = null, ConnectionInterface $db)
    {
        $this->db = $db;
        parent::__construct($serializer);
    }

    /**
     * {@inheritdoc}
     * @throws \Yiisoft\Db\Exception
     */
    public function has($key): bool
    {
        $key = $this->normalizeKey($key);

        $query = new Query();
        $query->select(['COUNT(*)'])
            ->from($this->cacheTable)
            ->where('[[id]] = :id AND ([[expire]] = 0 OR [[expire]] >' . time() . ')', [':id' => $key]);
        if ($this->db->enableQueryCache) {
            // temporarily disable and re-enable query caching
            $this->db->enableQueryCache = false;
            $result = $query->createCommand($this->db)->queryScalar();
            $this->db->enableQueryCache = true;
        } else {
            $result = $query->createCommand($this->db)->queryScalar();
        }

        return $result > 0;
    }

    /**
     * {@inheritdoc}
     * @throws \Yiisoft\Db\Exception
     */
    protected function getValue($key)
    {
        $query = (new Query())
            ->select(['data'])
            ->from($this->cacheTable)
            ->where('[[id]] = :id AND ([[expire]] = 0 OR [[expire]] >' . time() . ')', [':id' => $key]);

        if ($this->db->enableQueryCache) {
            // temporarily disable and re-enable query caching
            $this->db->enableQueryCache = false;
            $result = $query->createCommand($this->db)->queryScalar();
            $this->db->enableQueryCache = true;

            return $result;
        }

        return $query->createCommand($this->db)->queryScalar();
    }

    /**
     * {@inheritdoc}
     * @throws \Yiisoft\Db\Exception
     */
    protected function getValues($keys): array
    {
        if (empty($keys)) {
            return [];
        }
        $query = (new Query())
            ->select(['id', 'data'])
            ->from($this->cacheTable)
            ->where(['id' => $keys])
            ->andWhere('([[expire]] = 0 OR [[expire]] > ' . time() . ')');

        if ($this->db->enableQueryCache) {
            $this->db->enableQueryCache = false;
            $rows = $query->createCommand($this->db)->queryAll();
            $this->db->enableQueryCache = true;
        } else {
            $rows = $query->createCommand($this->db)->queryAll();
        }

        $results = array_fill_keys($keys, false);
        foreach ($rows as $row) {
            if (\is_resource($row['data']) && get_resource_type($row['data']) === 'stream') {
                $results[$row['id']] = stream_get_contents($row['data']);
            } else {
                $results[$row['id']] = $row['data'];
            }
        }

        return $results;
    }

    /**
     * Stores a value identified by a key in cache.
     * This method should be implemented by child classes to store the data
     * in specific cache storage.
     *
     * @param string $key   the key identifying the value to be cached
     * @param mixed  $value the value to be cached. Most often it's a string. If you have disabled [[serializer]],
     *                      it could be something else.
     * @param int    $ttl   the number of seconds in which the cached value will expire.
     *
     * @return bool true if the value is successfully stored into cache, false otherwise
     * @throws \Throwable
     */
    protected function setValue($key, $value, $ttl): bool
    {
        $this->db->noCache(function (Connection $db) use ($key, $value, $ttl) {
            $db->createCommand()->upsert($this->cacheTable, [
                'id' => $key,
                'expire' => $ttl > 0 ? $ttl + time() : 0,
                'data' => new PdoValue($value, \PDO::PARAM_LOB),
            ])->execute();
        });

        $this->gc();

        return true;
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key      the key identifying the value to be cached
     * @param string $value    the value to be cached. Other types (if you have disabled [[serializer]]) cannot be
     *                         saved.
     * @param int    $duration the number of seconds in which the cached value will expire. 0 means never expire.
     *
     * @return bool true if the value is successfully stored into cache, false otherwise
     * @throws \Throwable
     */
    protected function addValue($key, $value, $duration): bool
    {
        $this->gc();

        $this->db->noCache(function (Connection $db) use ($key, $value, $duration) {
            $db->createCommand()
                ->insert($this->cacheTable, [
                    'id' => $key,
                    'expire' => $duration > 0 ? $duration + time() : 0,
                    'data' => new PdoValue($value, \PDO::PARAM_LOB),
                ])->execute();
        });

        return true;
    }

    /**
     * {@inheritdoc}
     * @throws \Throwable
     */
    protected function deleteValue($key): bool
    {
        $this->db->noCache(function (Connection $db) use ($key) {
            $db->createCommand()
                ->delete($this->cacheTable, ['id' => $key])
                ->execute();
        });

        return true;
    }

    /**
     * Removes the expired data values.
     *
     * @param bool $force whether to enforce the garbage collection regardless of [[gcProbability]].
     *                    Defaults to false, meaning the actual deletion happens with the probability as specified by
     *                    [[gcProbability]].
     *
     * @throws \Exception
     */
    public function gc($force = false)
    {
        if ($force || random_int(0, 1000000) < $this->gcProbability) {
            $this->db->createCommand()
                ->delete($this->cacheTable, '[[expire]] > 0 AND [[expire]] < ' . time())
                ->execute();
        }
    }

    /**
     * {@inheritdoc}
     * @throws \Yiisoft\Db\Exception
     */
    public function clear(): bool
    {
        $this->db->createCommand()
            ->delete($this->cacheTable)
            ->execute();

        return true;
    }
}
