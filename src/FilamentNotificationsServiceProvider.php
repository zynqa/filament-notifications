<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentNotificationsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-notifications')
            ->hasConfigFile()
            ->hasMigrations([
                'create_admin_notifications_table',
                'create_notification_recipients_table',
                'add_email_delivery_to_admin_notifications_table',
            ])
            ->hasViews();
    }

    public function packageBooted(): void
    {
        \Zynqa\FilamentNotifications\Services\MailTemplateService::createDefaultTemplate();

        \Livewire\Livewire::component(
            'notification-read-sync',
            \Zynqa\FilamentNotifications\Livewire\NotificationReadSync::class
        );
    }
}
