<?php

namespace Yiisoft\Cache\Tests;

class MockHelper
{
    /**
     * @var int virtual time to be returned by mocked time() function.
     * null means normal time() behavior.
     */
    public static $mock_time;
    /**
     * @var string|false value to be returned by mocked json_encode() function.
     * null means normal json_encode() behavior.
     */
    public static $mock_json_encode;

    public static function resetMocks(): void
    {
        static::$mock_time = null;
        static::$mock_json_encode = null;
    }
}

namespace Yiisoft\Cache;

use Yiisoft\Cache\Tests\MockHelper;

/**
 * Mock for the time() function
 * @return int
 */
function time(): int
{
    return MockHelper::$mock_time ?? \time();
}

/**
 * Mock for the json_encode() function
 * @return string|false
 */
function json_encode($value, $options = 0, $depth = 512)
{
    return MockHelper::$mock_json_encode ?? \json_encode($value, $options, $depth);
}

namespace Yiisoft\Cache\Dependency;

use Yiisoft\Cache\Tests\MockHelper;

/**
 * Mock for the json_encode() function
 * @return string|false
 */
function json_encode($value, $options = 0, $depth = 512)
{
    return MockHelper::$mock_json_encode ?? \json_encode($value, $options, $depth);
}
