<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use InvalidArgumentException;
use TypeError;

use function is_int;
use function is_string;

/**
 * Value object representing a time-to-live (TTL) duration in seconds.
 *
 * ```php
 * $ttl = Ttl::minutes(5);
 * $seconds = $ttl->toSeconds(); // 300
 * ```
 *
 * @psalm-immutable
 */
final class Ttl
{
    private const SECONDS_IN_MINUTE = 60;
    private const SECONDS_IN_HOUR = 3600;
    private const SECONDS_IN_DAY = 86400;

    /**
     * @param int|null $value TTL value in seconds. Null represents "forever".
     */
    private function __construct(
        public readonly ?int $value,
    ) {}

    /**
     * Create TTL from a combination of seconds, minutes, hours and days.
     *
     * @param int $seconds Number of seconds.
     * @param int $minutes Number of minutes.
     * @param int $hours Number of hours.
     * @param int $days Number of days.
     */
    public static function create(
        int $seconds = 0,
        int $minutes = 0,
        int $hours = 0,
        int $days = 0,
    ): self {
        $totalSeconds = $seconds
            + $minutes * self::SECONDS_IN_MINUTE
            + $hours * self::SECONDS_IN_HOUR
            + $days * self::SECONDS_IN_DAY;

        return new self($totalSeconds);
    }

    /**
     * Creates a Ttl object from various TTL representations.
     *
     * Handles null, integers, numeric strings, DateInterval, and Ttl objects.
     *
     * @param DateInterval|int|string|Ttl|null $ttl Raw TTL value (string must be numeric, e.g., '3600')
     *
     * @throws TypeError For invalid TTL values types.
     *
     * @psalm-return self
     *
     * Example usage:
     *  ```php
     *  $ttl = Ttl::from(3600); // 1 hour
     *  $ttl = Ttl::from('3600'); // 1 hour
     *  $ttl = Ttl::from(new DateInterval('PT1H'));
     *  $ttl = Ttl::from(null); // infinity
     *  ```
     */
    public static function from(self|DateInterval|int|string|null $ttl): self
    {
        return match (true) {
            $ttl === null => self::forever(),
            $ttl instanceof self => $ttl,
            $ttl instanceof DateInterval => self::fromInterval($ttl),
            is_string($ttl) => self::seconds((int) $ttl),
            is_int($ttl) => self::seconds($ttl),
        };
    }

    /**
     * Creates a Ttl object from a DateInterval.
     *
     * @param DateInterval $interval The interval to convert to TTL.
     *
     * @throws InvalidArgumentException If the DateInterval results in a negative TTL.
     *
     * @return self TTL instance.
     */
    public static function fromInterval(DateInterval $interval): self
    {
        $seconds = (new DateTime('@0'))
            ->add($interval)
            ->getTimestamp();

        return new self($seconds);
    }

    /**
     * Create TTL from seconds.
     *
     * @param int $seconds Number of seconds.
     *
     * @return self TTL instance.
     */
    public static function seconds(int $seconds): self
    {
        return new self($seconds);
    }

    /**
     * Create TTL from minutes.
     *
     * @param int $minutes Number of minutes.
     *
     * @return self TTL instance.
     */
    public static function minutes(int $minutes): self
    {
        return new self($minutes * self::SECONDS_IN_MINUTE);
    }

    /**
     * Create TTL from hours.
     *
     * @param int $hours Number of hours.
     *
     * @return self TTL instance.
     */
    public static function hours(int $hours): self
    {
        return new self($hours * self::SECONDS_IN_HOUR);
    }

    /**
     * Create TTL from days.
     *
     * @param int $days Number of days.
     *
     * @return self TTL instance.
     */
    public static function days(int $days): self
    {
        return new self($days * self::SECONDS_IN_DAY);
    }

    /**
     * Creates a TTL representing "forever" (no expiration).
     */
    public static function forever(): self
    {
        return new self(null);
    }

    /**
     * Checks whether the TTL represents "forever".
     */
    public function isForever(): bool
    {
        return $this->value === null;
    }

    /**
     * Get TTL value in seconds or null if forever.
     *
     * @return int|null
     */
    public function toSeconds(): ?int
    {
        return $this->value;
    }
}
