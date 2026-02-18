<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Settings;

use Spatie\LaravelSettings\Settings;

class NotificationSettings extends Settings
{
    public ?string $default_email_template = 'default.blade.php';

    public static function group(): string
    {
        return 'filament-notifications';
    }
}
