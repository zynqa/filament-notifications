# Installation Guide - Filament Notifications Package

This guide walks you through installing the `zynqa/filament-notifications` package in your Laravel/Filament application.

## Prerequisites

Before installing, ensure you have:

- ✅ PHP 8.2 or higher
- ✅ Laravel 11.0 or higher
- ✅ FilamentPHP 3.2 or higher installed and configured
- ✅ Database configured (MySQL, PostgreSQL, SQLite, etc.)
- ✅ (Optional) Filament Shield for permissions management

## Installation Steps

### Step 1: Install Package via Composer

#### Option A: From Packagist (Production)

```bash
composer require zynqa/filament-notifications
```

#### Option B: Local Development

If developing locally, add to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/zynqa/filament-notifications"
        }
    ],
    "require": {
        "zynqa/filament-notifications": "@dev"
    }
}
```

Then run:

```bash
composer require zynqa/filament-notifications:@dev --prefer-source
```

### Step 2: Publish Migrations

```bash
php artisan vendor:publish --tag="filament-notifications-migrations"
```

This creates two migration files in `database/migrations/`:
- `YYYY_MM_DD_HHMMSS_create_admin_notifications_table.php`
- `YYYY_MM_DD_HHMMSS_create_notification_recipients_table.php`

### Step 3: Run Migrations

```bash
php artisan migrate
```

Expected output:
```
INFO  Running migrations.

  YYYY_MM_DD_HHMMSS_create_admin_notifications_table ............ DONE
  YYYY_MM_DD_HHMMSS_create_notification_recipients_table ........ DONE
```

### Step 4: Register Plugin

Edit your Filament Panel Provider (typically `app/Providers/Filament/AppPanelProvider.php`):

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Zynqa\FilamentNotifications\FilamentNotificationsPlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ... existing configuration ...
            ->plugins([
                // ... other plugins ...
                FilamentNotificationsPlugin::make(),
            ])
            ->databaseNotifications() // Ensure this is enabled
            ->databaseNotificationsPolling('30s'); // Optional: customize polling interval
    }
}
```

**Important:** Ensure `->databaseNotifications()` is enabled for the notification bell to work.

### Step 5: Add User Model Relationships

Edit `app/Models/User.php` and add these two methods:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Zynqa\FilamentNotifications\Models\AdminNotification;

class User extends Authenticatable
{
    // ... existing code ...

    /**
     * Get all admin notifications received by this user
     */
    public function adminNotifications(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            AdminNotification::class,
            'notification_recipients'
        )
            ->withPivot('read_at')
            ->withTimestamps()
            ->orderByPivot('created_at', 'desc');
    }

    /**
     * Get only unread admin notifications
     */
    public function unreadAdminNotifications(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->adminNotifications()->wherePivotNull('read_at');
    }
}
```

### Step 6: Configure Permissions (Optional, if using Filament Shield)

If you're using [Filament Shield](https://github.com/bezhanSalleh/filament-shield) for permissions:

```bash
php artisan shield:generate --all
```

This generates permissions like:
- `view_any_admin::notification`
- `view_admin::notification`
- `create_admin::notification`
- `update_admin::notification`
- `delete_admin::notification`

Then assign these permissions to roles via the Filament Shield UI:
1. Navigate to Roles & Permissions
2. Edit the `super_admin` role (or your desired role)
3. Check all `admin::notification` permissions
4. Save

### Step 7: Publish Config (Optional)

If you want to customize navigation settings, icons, or permissions:

```bash
php artisan vendor:publish --tag="filament-notifications-config"
```

This creates `config/filament-notifications.php`:

```php
return [
    'navigation' => [
        'group' => 'Users & Roles',  // Change navigation group
        'sort' => 3,                 // Change sort order
        'icon' => 'heroicon-o-bell-alert',  // Change navigation icon
    ],
    'permissions' => [
        'enabled' => true,           // Enable/disable permission checks
        'prefix' => 'admin::notification',  // Permission prefix
    ],
    'default_icons' => [
        // Add or remove available heroicons
    ],
];
```

### Step 8: Clear Cache

```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

## Verification

### 1. Check Database Tables

```bash
php artisan tinker
```

```php
DB::table('admin_notifications')->count(); // Should return 0 initially
DB::table('notification_recipients')->count(); // Should return 0 initially
exit
```

### 2. Access Filament Panel

1. Login to your Filament panel (e.g., `http://yourapp.test/admin`)
2. Look for **"Notifications"** in the navigation menu (under "Users & Roles" by default)
3. Click to access the notifications management interface

### 3. Test Creating a Notification

1. Click **"Create"** in the Notifications page
2. Fill in:
   - Title: "Test Notification"
   - Body: "This is a test message"
   - Type: Info
   - Icon: Bell
   - Recipients: Select yourself
3. Click **"Create"**
4. Verify it appears in the list with a document icon (draft status)

### 4. Test Sending a Notification

1. Click **"View"** on the test notification
2. Click **"Send Notification"** button
3. Confirm the action
4. Check your notification bell (top right of Filament panel)
5. You should see the notification appear

## Troubleshooting

### Issue: "Notifications" menu item doesn't appear

**Solution:**
- Ensure `FilamentNotificationsPlugin::make()` is added to `->plugins()` in your Panel Provider
- Clear cache: `php artisan cache:clear`
- Check permissions if using Filament Shield

### Issue: Notification bell doesn't show notifications

**Solution:**
- Ensure `->databaseNotifications()` is enabled in Panel config
- Check that notification was sent (not still a draft)
- Verify User model has the required relationships
- Check database: `DB::table('notifications')->count()`

### Issue: "Class not found" errors

**Solution:**
- Run: `composer dump-autoload`
- Clear cache: `php artisan cache:clear`
- Ensure package is properly installed: `composer show zynqa/filament-notifications`

### Issue: Migration errors

**Solution:**
- Ensure your database connection is configured correctly
- Check if tables already exist: `php artisan migrate:status`
- If tables exist, you can skip: `php artisan migrate --skip`

### Issue: Permission denied errors

**Solution:**
- If using Filament Shield:
  - Regenerate permissions: `php artisan shield:generate --all`
  - Assign permissions to your role via Shield UI
- If not using Shield, set `'permissions.enabled' => false` in config

## Next Steps

After successful installation:

1. **Customize Navigation** - Edit `config/filament-notifications.php` to change navigation group/icon
2. **Set Up Permissions** - Configure which roles can create/send notifications
3. **Create First Notification** - Test the full workflow from creation to delivery
4. **Train Your Team** - Show admins how to create and send notifications
5. **Monitor Usage** - Check read rates and recipient engagement

## Uninstallation

If you need to remove the package:

```bash
# 1. Remove from composer
composer remove zynqa/filament-notifications

# 2. Rollback migrations (CAUTION: deletes all notification data)
php artisan migrate:rollback --step=2

# 3. Remove config (if published)
rm config/filament-notifications.php

# 4. Remove User model relationships
# Manually remove the adminNotifications() and unreadAdminNotifications() methods from User.php

# 5. Clear cache
php artisan cache:clear
php artisan config:cache
```

## Support

For issues, questions, or feature requests:
- Open an issue on GitHub
- Email: info@zynqa.com
- Documentation: See README.md

## Upgrading

When a new version is released:

```bash
# Update via composer
composer update zynqa/filament-notifications

# Publish new migrations (if any)
php artisan vendor:publish --tag="filament-notifications-migrations" --force

# Run migrations
php artisan migrate

# Clear cache
php artisan cache:clear
php artisan config:cache
```

Check CHANGELOG.md for breaking changes and migration guides.
