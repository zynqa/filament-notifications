<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('filament-notifications.default_email_template', 'default.blade.php');
    }

    public function down(): void
    {
        $this->migrator->delete('filament-notifications.default_email_template');
    }
};
