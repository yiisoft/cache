<?php
namespace Yiisoft\CacheOld\Serializer;

/**
 * JsonSerializer serializes data in JSON format.
 */
final class JsonSerializer implements SerializerInterface
{
    private $options;

    /**
     * JsonSerializer constructor.
     *
     * @param int $options integer the encoding options. For more details please refer to
     * <http://www.php.net/manual/en/function.json-encode.php>.
     * Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     */
    public function __construct(int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    {
        $this->options = $options;
    }

    public function serialize($value): string
    {
        return json_encode($value, $this->options);
    }

    public function unserialize(string $value)
    {
        return json_decode($value, true);
    }
}
