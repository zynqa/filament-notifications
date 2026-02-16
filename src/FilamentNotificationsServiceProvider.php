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
            ]);
    }
}
