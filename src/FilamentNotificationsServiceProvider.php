<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Zynqa\FilamentNotifications\Services\SubscriptionNotificationService;

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
                'create_entity_subscriptions_table',
                'create_entity_type_settings_table',
                'create_notification_preferences_table',
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

        // Register migrations so they are discovered by `php artisan migrate`
        // without requiring a manual copy to the host app's database/migrations directory.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/settings');

        // Ensure settings exist in database (in case migration didn't run properly)
        $this->ensureSettingsExist();
    }

    public function packageRegistered(): void
    {
        // Register SubscriptionNotificationService as a singleton
        $this->app->singleton(SubscriptionNotificationService::class);
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
