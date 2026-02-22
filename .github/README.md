# Filament Notifications

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zynqa/filament-notifications.svg?style=flat-square)](https://packagist.org/packages/zynqa/filament-notifications)
[![Total Downloads](https://img.shields.io/packagist/dt/zynqa/filament-notifications.svg?style=flat-square)](https://packagist.org/packages/zynqa/filament-notifications)

A FilamentPHP v3 package for creating and managing custom notifications sent to users through your admin panel. Send database and email notifications with read tracking, customizable templates, and full Shield integration.

## Features

- **Database & Email Delivery** - Send notifications via database (notification bell) or email
- **Custom Email Templates** - Create and manage custom email templates with full Blade support
- **Draft & Send Workflow** - Save as draft, review, then send when ready
- **Read Tracking** - Track which users have read each notification
- **Multi-User Selection** - Send to one or multiple users at once
- **Customizable Appearance** - Choose from 13+ heroicons and colors
- **Shield Integration** - Full permission system integration
- **Soft Deletes** - Complete audit trail with soft delete support

## Requirements

- PHP 8.2+
- Laravel 11.0+
- FilamentPHP 3.2+

## Installation

### 1. Install via Composer

```bash
composer require zynqa/filament-notifications
```

### 2. Run Migrations

```bash
php artisan migrate
```

This creates:
- `admin_notifications` - Notification content and metadata
- `notification_recipients` - Recipient tracking with read status
- `notification_settings` - Email template preferences

### 3. Register Plugin

Add to your Filament Panel Provider (e.g., `app/Providers/Filament/AppPanelProvider.php`):

```php
use Zynqa\FilamentNotifications\FilamentNotificationsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentNotificationsPlugin::make(),
        ])
        ->databaseNotifications(); // Required for notification bell
}
```

### 4. Add User Model Relationships

Add to `app/Models/User.php`:

```php
use Zynqa\FilamentNotifications\Models\AdminNotification;

public function adminNotifications(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->belongsToMany(AdminNotification::class, 'notification_recipients')
        ->withPivot('read_at')
        ->withTimestamps()
        ->orderByPivot('created_at', 'desc');
}

public function unreadAdminNotifications(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->adminNotifications()->wherePivotNull('read_at');
}
```

### 5. Configure Permissions (Optional)

If using Filament Shield:

```bash
php artisan shield:generate --all
```

Then assign `admin::notification` permissions to roles via the Shield UI.

## Usage

### Creating and Sending Notifications

1. Navigate to **Notifications** in your admin panel
2. Click **Create**
3. Fill in the form:
   - **Title** - Brief headline
   - **Body** - Detailed message
   - **Delivery Method** - Database (bell) or Email & Database
   - **Type** - Info, Success, Warning, or Danger
   - **Icon** & **Color** - Choose from available options
   - **Recipients** - Select one or more users
4. Click **Create** (saves as draft)
5. Review and click **Send Notification**

Recipients will see the notification in their notification bell (database) or receive an email, depending on the delivery method selected.

### Email Templates

#### Using Existing Templates

1. Navigate to **Settings** → **General Settings**
2. Scroll to **Notification Settings**
3. Select your preferred template from the dropdown
4. Click **Save**

#### Creating Custom Templates

Email templates are stored in `storage/app/mail-templates/` and are automatically discovered.

**Step 1:** Create a new Blade file in `storage/app/mail-templates/`

Example: `storage/app/mail-templates/urgent-alert.blade.php`

**Step 2:** Use the available variables in your template:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .alert {
            background-color: {{ $notification_type === 'danger' ? '#fee' : '#efe' }};
            padding: 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="alert">
            <h1>{{ $title }}</h1>
            <p>{!! nl2br(e($body)) !!}</p>

            @if($url)
                <a href="{{ $url }}" style="display: inline-block; padding: 10px 20px;
                   background: #3b82f6; color: white; text-decoration: none;
                   border-radius: 5px;">View Details</a>
            @endif
        </div>

        <footer style="margin-top: 30px; text-align: center; color: #6b7280;">
            <p>{{ config('app.name') }}</p>
        </footer>
    </div>
</body>
</html>
```

**Available Variables:**

- `$title` (string) - Notification title
- `$body` (string) - Notification body text
- `$url` (string|null) - Optional action URL
- `$notification_type` (string) - 'info', 'success', 'warning', or 'danger'
- `$icon` (string) - Heroicon name (e.g., 'heroicon-o-bell')
- `$icon_color` (string) - 'primary', 'success', 'warning', 'danger', 'info', or 'gray'

**Step 3:** Save the file

The template will automatically appear in the General Settings dropdown with a formatted name (e.g., "Urgent Alert").

**Tips:**
- Use inline CSS for best email client compatibility
- Always escape user content with `e()` or `{!! nl2br(e($body)) !!}`
- Test across different email clients
- Keep designs simple and mobile-friendly

## Configuration

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="filament-notifications-config"
```

Customize navigation, permissions, and icons in `config/filament-notifications.php`.

## Permissions

When using Filament Shield, these permissions are created:

- `view_any_admin::notification` - View notifications list
- `view_admin::notification` - View individual notification
- `create_admin::notification` - Create new notifications
- `update_admin::notification` - Edit drafts
- `delete_admin::notification` - Delete drafts
- `restore_admin::notification` - Restore soft-deleted notifications
- `force_delete_admin::notification` - Permanently delete notifications

**Note:** Sent notifications are read-only and cannot be edited or deleted.

## Support

- **Issues:** [GitHub Issues](https://github.com/zynqa/filament-notifications/issues)
- **Email:** info@zynqa.com

## License

MIT License. See [LICENSE.md](../LICENSE.md) for details.
