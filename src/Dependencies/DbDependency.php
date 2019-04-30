<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Dependencies;

use Yiisoft\Cache\Exceptions\InvalidConfigException;
use yii\db\ConnectionInterface;

/**
 * DbDependency represents a dependency based on the query result of a SQL statement.
 *
 * If the query result changes, the dependency is considered as changed.
 * The query is specified via the [[sql]] property.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
class DbDependency extends Dependency
{
    /**
     * @var ConnectionInterface DB connection.
     */
    public $db;
    /**
     * @var string the SQL query whose result is used to determine if the dependency has been changed.
     * Only the first row of the query result will be used.
     */
    public $sql;
    /**
     * @var array the parameters (name => value) to be bound to the SQL statement specified by [[sql]].
     */
    public $params = [];


    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Generates the data needed to determine if dependency has been changed.
     * This method returns the value of the global state.
     * @param \Yiisoft\Cache\CacheInterface $cache the cache component that is currently evaluating this dependency
     * @return mixed the data needed to determine if dependency has been changed.
     * @throws InvalidConfigException if [[db]] is not a valid application component ID
     */
    protected function generateDependencyData($cache)
    {
        /* @var $db ConnectionInterface */
        if ($this->sql === null) {
            throw new InvalidConfigException('DbDependency::sql must be set.');
        }

        if ($this->db->enableQueryCache) {
            // temporarily disable and re-enable query caching
            $this->db->enableQueryCache = false;
            $result = $this->db->createCommand($this->sql, $this->params)->queryOne();
            $this->db->enableQueryCache = true;
        } else {
            $result = $this->db->createCommand($this->sql, $this->params)->queryOne();
        }

        return $result;
    }
}
