<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

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

    private function __construct(public readonly int $value)
    {
    }

    /**
     * Create TTL from a combination of seconds, minutes, hours and days.
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

        return new self($totalSeconds);
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
     * Get TTL value in seconds.
     *
     * @return int Number of seconds.
     */
    public function toSeconds(): int
    {
        return $this->value;
    }
}
