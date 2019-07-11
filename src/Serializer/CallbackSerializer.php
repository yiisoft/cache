<?php
namespace Yiisoft\Cache\Serializer;

/**
 * CallbackSerializer serializes data via custom PHP callback.
 */
class CallbackSerializer implements SerializerInterface
{
    /**
     * @var callable PHP callback, which should be used to serialize value.
     */
    private $serialize;
    /**
     * @var callable PHP callback, which should be used to unserialize value.
     */
    private $unserialize;

    public function __construct($serialize, $unserialize)
    {
        $this->serialize = $serialize;
        $this->unserialize = $unserialize;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($value): string
    {
        return call_user_func($this->serialize, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize(string $value)
    {
        return call_user_func($this->unserialize, $value);
    }
}
