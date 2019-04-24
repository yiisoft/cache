<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Dependencies;

use yii\exceptions\InvalidConfigException;
use yii\helpers\Yii;

/**
 * FileDependency represents a dependency based on a file's last modification time.
 *
 * If the last modification time of the file specified via [[fileName]] is changed,
 * the dependency is considered as changed.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
class FileDependency extends Dependency
{
    /**
     * @var string the file path or [path alias](guide:concept-aliases) whose last modification time is used to
     * check if the dependency has been changed.
     */
    public $fileName;


    /**
     * Generates the data needed to determine if dependency has been changed.
     * This method returns the file's last modification time.
     * @param \Yiisoft\Cache\CacheInterface $cache the cache component that is currently evaluating this dependency
     * @return mixed the data needed to determine if dependency has been changed.
     * @throws InvalidConfigException if [[fileName]] is not set
     */
    protected function generateDependencyData($cache)
    {
        if ($this->fileName === null) {
            throw new InvalidConfigException('FileDependency::fileName must be set');
        }

        $fileName = Yii::getAlias($this->fileName);
        clearstatcache(false, $fileName);
        return @filemtime($fileName);
    }
}
