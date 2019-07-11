<?php
namespace Yiisoft\Cache\Serializer;

/**
 * CallbackSerializer serializes data via custom PHP callback.
 */
final class CallbackSerializer implements SerializerInterface
{
    /**
     * @var callable PHP callback, which should be used to serialize value.
     */
    private $serializeCallback;
    /**
     * @var callable PHP callback, which should be used to unserialize value.
     */
    private $unserializeCallback;

    public function __construct(callable $serializeCallback, callable $unserializeCallback)
    {
        $this->serializeCallback = $serializeCallback;
        $this->unserializeCallback = $unserializeCallback;
    }

    public function serialize($value): string
    {
        return \call_user_func($this->serializeCallback, $value);
    }

    public function unserialize(string $value)
    {
        return \call_user_func($this->unserializeCallback, $value);
    }
}
