<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;

/**
 * Value object representing a time-to-live (TTL) duration in seconds.
 *
 * ```php
 * $ttl = Ttl::minutes(5);
 * $seconds = $ttl->toSeconds(); // 300
 * ```
 */
final class Ttl
{
    private const SECONDS_IN_MINUTE = 60;
    private const SECONDS_IN_HOUR = 3600;
    private const SECONDS_IN_DAY = 86400;

    private const INT_PLACEHOLDER_FOR_FOREVER_VALUE = 0;

    private function __construct(
        public readonly int $value,
        public readonly bool $isForever = false
    ) {
        if (!$isForever && $value < 0) {
            throw new \InvalidArgumentException('TTL must be non-negative.');
        }
    }

    /**
     * Create TTL from a combination of seconds, minutes, hours and days.
     *
     * @throws \InvalidArgumentException If the $totalSeconds results in a negative TTL.
     *
     * @param int $sec Number of seconds.
     * @param int $min Number of minutes.
     * @param int $hour Number of hours.
     * @param int $day Number of days.
     */
    public static function create(
        int $sec = 0,
        int $min = 0,
        int $hour = 0,
        int $day = 0,
    ): self {
        $totalSeconds = $sec
            + $min * self::SECONDS_IN_MINUTE
            + $hour * self::SECONDS_IN_HOUR
            + $day * self::SECONDS_IN_DAY;

        if ($totalSeconds < 0) {
            throw new \InvalidArgumentException('TTL must be non-negative.');
        }

        return new self($totalSeconds);
    }

    /**
     * Creates a Ttl object from various TTL representations.
     *
     * Handles null, integers, numeric strings, DateInterval, and Ttl objects.
     *
     * @param DateInterval|int|string|Ttl|null $ttl Raw TTL value (string must be numeric, e.g., '3600')
     *
     * @throws \InvalidArgumentException For invalid TTL values (e.g., negative duration or invalid string).
     *
     * @return Ttl Normalized TTL object.
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
            is_string($ttl), is_int($ttl) => self::seconds((int) $ttl),
            default => throw new \InvalidArgumentException('Invalid TTL value: must be int, string, DateInterval, Ttl, or null.'),
        };
    }

    /**
     * Creates a Ttl object from a DateInterval.
     *
     * @param DateInterval $interval The interval to convert to TTL.
     *
     * @throws \InvalidArgumentException If the DateInterval results in a negative TTL.
     *
     * @return self TTL instance.
     */
    public static function fromInterval(DateInterval $interval): self
    {
        $seconds = (new DateTime('@0'))
            ->add($interval)
            ->getTimestamp();

        if ($seconds < 0) {
            throw new \InvalidArgumentException('DateInterval must result in non-negative TTL.');
        }

        return new self($seconds);
    }

    /**
     * Create TTL from seconds.
     *
     * @param int $sec Number of seconds.
     *
     * @return self TTL instance.
     */
    public static function seconds(int $sec): self
    {
        return new self($sec);
    }

    /**
     * Create TTL from minutes.
     *
     * @param int $min Number of minutes.
     *
     * @return self TTL instance.
     */
    public static function minutes(int $min): self
    {
        return new self($min * self::SECONDS_IN_MINUTE);
    }

    /**
     * Create TTL from hours.
     *
     * @param int $hour Number of hours.
     *
     * @return self TTL instance.
     */
    public static function hours(int $hour): self
    {
        return new self($hour * self::SECONDS_IN_HOUR);
    }

    /**
     * Create TTL from days.
     *
     * @param int $day Number of days.
     *
     * @return self TTL instance.
     */
    public static function days(int $day): self
    {
        return new self($day * self::SECONDS_IN_DAY);
    }

    /**
     * Creates a TTL representing "forever" (no expiration).
     */
    public static function forever(): self
    {
        return new self(self::INT_PLACEHOLDER_FOR_FOREVER_VALUE, true);
    }

    /**
     * Get TTL value in seconds or null if forever.
     */
    public function toSeconds(): ?int
    {
        return $this->isForever ? null : $this->value;
    }
}
