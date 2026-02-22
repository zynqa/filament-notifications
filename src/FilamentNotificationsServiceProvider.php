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

        // Register the settings migration so it is discovered by `php artisan migrate`
        // without requiring a manual copy to the host app's database/settings directory.
        $this->loadMigrationsFrom(__DIR__.'/../database/settings');

        // Ensure settings exist in database (in case migration didn't run properly)
        $this->ensureSettingsExist();
    }

    /**
     * Ensure notification settings exist in database
     */
    protected function ensureSettingsExist(): void
    {
        try {
            // Only run if settings table exists and we're not in console (to avoid issues during migrations)
            if (! app()->runningInConsole() || app()->runningUnitTests()) {
                if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                    $exists = \Illuminate\Support\Facades\DB::table('settings')
                        ->where('group', 'filament-notifications')
                        ->where('name', 'default_email_template')
                        ->exists();

                    if (! $exists) {
                        \Illuminate\Support\Facades\DB::table('settings')->insert([
                            'group' => 'filament-notifications',
                            'name' => 'default_email_template',
                            'locked' => 0,
                            'payload' => json_encode('default.blade.php'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if there's any issue (e.g., database not set up yet)
        }
    }
}
