<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Support;

/**
 * Resolves a user's stored notification-channel preference into an effective channel.
 *
 * Channels:
 *  - off      => do not notify at all for this type
 *  - database => in-app bell only
 *  - email    => email only
 *  - both     => in-app bell and email
 *
 * When no preference (or an invalid value) is stored, we fall back to `database`,
 * which preserves the historical default behaviour (in-app only, no surprise emails).
 */
class NotificationChannelResolver
{
    public const DEFAULT = 'database';

    /**
     * @var array<int, string>
     */
    public const CHANNELS = ['off', 'database', 'email', 'both'];

    public static function resolve(?string $stored): string
    {
        return in_array($stored, self::CHANNELS, true) ? $stored : self::DEFAULT;
    }

    public static function isMuted(string $channel): bool
    {
        return $channel === 'off';
    }

    /**
     * Human-readable options for the preferences Select component.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'off' => 'Off',
            'database' => 'In-app only',
            'email' => 'Email only',
            'both' => 'In-app and email',
        ];
    }
}
