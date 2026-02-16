<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Facades\Gate;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource;
use Zynqa\FilamentNotifications\Models\AdminNotification;
use Zynqa\FilamentNotifications\Policies\AdminNotificationPolicy;

class FilamentNotificationsPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'filament-notifications';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AdminNotificationResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // Register policy
        Gate::policy(
            AdminNotification::class,
            AdminNotificationPolicy::class
        );
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }
}
