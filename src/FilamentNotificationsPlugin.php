<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
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

        // Inject the invisible Livewire component that syncs notification_recipients.read_at
        // when Filament's markedNotificationAsRead event fires (raw query bypasses model events)
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): HtmlString => new HtmlString(Blade::render('<livewire:notification-read-sync />')),
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
