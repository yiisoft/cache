<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Serializer;

//use yii\helpers\Json;

/**
 * JsonSerializer serializes data in JSON format.
 */
class JsonSerializer implements SerializerInterface
{
    private $options;

    /**
     * JsonSerializer constructor.
     *
     * @param int $options integer the encoding options. For more details please refer to
     * <http://www.php.net/manual/en/function.json-encode.php>.
     * Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     */
    public function __construct($options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    {
        $this->options = $options;
    }


    /**
     * {@inheritdoc}
     */
    public function serialize($value): string
    {
        return json_encode($value, $this->options); //FIXME: Json::encode($value, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize(string $value)
    {
        return json_decode($value, true); //FIXME: Json::decode($value);
    }
}
