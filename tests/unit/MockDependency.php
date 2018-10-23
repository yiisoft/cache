<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\cache\tests\unit;

use yii\cache\dependencies\Dependency;

/**
 * Class MockDependency.
 *
 * @author Boudewijn Vahrmeijer <vahrmeijer@gmail.com>
 */
class MockDependency extends Dependency
{
    protected function generateDependencyData($cache)
    {
        return $this->data;
    }
}
