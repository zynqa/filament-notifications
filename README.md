# Filament Notifications

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zynqa/filament-notifications.svg?style=flat-square)](https://packagist.org/packages/zynqa/filament-notifications)
[![Total Downloads](https://img.shields.io/packagist/dt/zynqa/filament-notifications.svg?style=flat-square)](https://packagist.org/packages/zynqa/filament-notifications)

Admin notification management system for FilamentPHP v3. Create, manage, and track custom notifications sent to users through your Filament admin panel.

## Features

✅ **Create Notifications via UI** - No command line required, use Filament's intuitive interface
✅ **Multi-User Selection** - Send to one or multiple users at once
✅ **Customizable Appearance** - Choose from 13+ heroicons, colors, and notification types
✅ **Draft & Send Workflow** - Save as draft, review, then send when ready
✅ **Read Tracking** - Track which users have read each notification
✅ **Recipient Analytics** - View read/unread counts and detailed recipient lists
✅ **Shield Integration** - Full permission system integration with Filament Shield
✅ **Persistent Notifications** - Notifications remain in user's notification bell until dismissed
✅ **Soft Deletes** - Complete audit trail with soft delete support

## Screenshots

*Coming soon*

## Installation

### Requirements

- PHP 8.2+
- Laravel 11.0+
- FilamentPHP 3.2+

### 1. Install via Composer

```bash
composer require zynqa/filament-notifications
```

### 2. Publish and Run Migrations

```bash
php artisan vendor:publish --tag="filament-notifications-migrations"
php artisan migrate
```

This creates two tables:
- `admin_notifications` - Stores notification content and metadata
- `notification_recipients` - Pivot table tracking recipients and read status

### 3. Register Plugin

Add the plugin to your Filament Panel Provider:

```php
// app/Providers/Filament/AppPanelProvider.php

use Zynqa\FilamentNotifications\FilamentNotificationsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other configuration
        ->plugins([
            FilamentNotificationsPlugin::make(),
        ]);
}
```

### 4. Add User Model Relationships

Add these methods to your `app/Models/User.php`:

```php
use Zynqa\FilamentNotifications\Models\AdminNotification;

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
```

### 5. Configure Permissions (Optional)

If using Filament Shield:

```bash
php artisan shield:generate --all
```

Then assign `admin::notification` permissions to appropriate roles via the Filament Shield UI.

## Usage

### Creating a Notification

1. Navigate to **Notifications** in your Filament panel navigation
2. Click **Create**
3. Fill in the form:
   - **Title**: Brief notification headline
   - **Body**: Detailed message content
   - **Type**: Info, Success, Warning, or Danger
   - **Icon**: Choose from 13+ heroicons
   - **Icon Color**: Primary, Success, Warning, Danger, Info, or Gray
   - **Recipients**: Select one or more users
4. Click **Create** (saves as draft)

### Reviewing and Sending

1. Navigate to the notification (it appears with a document icon indicating draft status)
2. Click **View** to review details
3. Check the **Recipients** tab to see who will receive it
4. Click **Send Notification** button
5. Confirm the action

Once sent:
- Recipients immediately see the notification in their notification bell
- Status changes to "Sent" (paper airplane icon)
- Edit and Delete buttons are disabled (sent notifications are read-only)

### Tracking Read Status

1. Click **View** on any sent notification
2. Go to the **Recipients** tab
3. See:
   - ✅ Check circle icon = Read
   - 🕐 Clock icon = Unread
   - **Read At** timestamp for each recipient
   - Filter by read/unread status

### Table Filters

The notifications list supports:
- **Type Filter**: Info, Success, Warning, Danger
- **Status Filter**: Sent or Drafts
- **Created by Me**: Show only notifications you created
- **Search**: Search by title or creator name

## Configuration

### Publishing Config

Optionally publish the config file to customize settings:

```bash
php artisan vendor:publish --tag="filament-notifications-config"
```

### Available Options

```php
// config/filament-notifications.php

return [
    // Navigation settings
    'navigation' => [
        'group' => 'Users & Roles',  // Navigation group
        'sort' => 3,                 // Sort order
        'icon' => 'heroicon-o-bell-alert',  // Navigation icon
    ],

    // Permission settings
    'permissions' => [
        'enabled' => true,           // Enable permission checking
        'prefix' => 'admin::notification',  // Permission prefix
    ],

    // Default icon options (13 heroicons included)
    'default_icons' => [
        'heroicon-o-bell' => 'Bell',
        'heroicon-o-bell-alert' => 'Bell Alert',
        // ... more icons
    ],
];
```

## Database Structure

### admin_notifications Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| title | string | Notification title |
| body | text | Notification message body |
| icon | string | Heroicon identifier |
| icon_color | string | Icon color (primary, success, etc.) |
| notification_type | enum | Type (info, success, warning, danger) |
| created_by | bigint | Foreign key to users table |
| sent_at | timestamp | NULL = draft, set = sent |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |
| deleted_at | timestamp | Soft delete timestamp |

### notification_recipients Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| admin_notification_id | bigint | Foreign key to admin_notifications |
| user_id | bigint | Foreign key to users |
| read_at | timestamp | NULL = unread, set = read |
| created_at | timestamp | When notification was received |
| updated_at | timestamp | Last update timestamp |

## Model Usage

### AdminNotification Model

```php
use Zynqa\FilamentNotifications\Models\AdminNotification;

// Get all sent notifications
$sent = AdminNotification::sent()->get();

// Get all drafts
$drafts = AdminNotification::draft()->get();

// Check if notification is sent
$notification->isSent(); // true/false
$notification->isDraft(); // true/false

// Get read analytics
$notification->read_count;     // Number who read
$notification->unread_count;   // Number who haven't read
$notification->total_recipients; // Total recipients

// Relationships
$notification->creator;         // User who created it
$notification->recipients;      // All recipients
$notification->readRecipients;  // Only those who read it
$notification->unreadRecipients; // Only those who haven't read
```

### User Model Methods

```php
// Get all admin notifications for user
$user->adminNotifications;

// Get only unread notifications
$user->unreadAdminNotifications;

// Count unread notifications
$user->unreadAdminNotifications()->count();
```

## Permissions

When using Filament Shield, these permissions are created:

- `view_any_admin::notification` - View notifications list
- `view_admin::notification` - View individual notification
- `create_admin::notification` - Create new notifications
- `update_admin::notification` - Edit drafts (sent notifications cannot be edited)
- `delete_admin::notification` - Delete drafts (sent notifications cannot be deleted)
- `restore_admin::notification` - Restore soft-deleted notifications
- `force_delete_admin::notification` - Permanently delete notifications

Assign these to the `super_admin` role or create custom roles via Filament Shield.

## Important Notes

### Sent Notifications are Read-Only

Once a notification is sent:
- It **cannot be edited** - Policy prevents updates
- It **cannot be deleted** - Policy prevents deletion
- Recipients list is frozen
- This ensures notification integrity and audit trail

### Draft Workflow

1. Create notification → Saved as draft (`sent_at = NULL`)
2. Review in View page
3. Send notification → `sent_at` set to current timestamp
4. Recipients receive notification via Filament's database channel
5. Notification becomes read-only

### Performance Considerations

- Notifications are sent synchronously for immediate delivery
- For large recipient lists (100+ users), consider adding queueing in a future update
- Database queries use proper indexing for read status filtering
- Soft deletes maintain full audit trail without data loss

## Troubleshooting

### Notifications Not Appearing in User's Bell

1. Ensure `->databaseNotifications()` is enabled in your Panel config:
   ```php
   $panel->databaseNotifications()
   ```

2. Check if user has unread notifications:
   ```php
   $user->unreadNotifications()->count()
   ```

3. Verify notification was actually sent (`sent_at` is not null)

### Permissions Not Working

1. Regenerate permissions:
   ```bash
   php artisan shield:generate --all
   ```

2. Clear cache:
   ```bash
   php artisan cache:clear
   ```

3. Ensure user has appropriate role assigned

### Recipients Not Being Saved

1. Verify User model has the required relationships
2. Check migration was run successfully:
   ```bash
   php artisan migrate:status
   ```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security-related issues, please email info@zynqa.com instead of using the issue tracker.

## Credits

- [Zynqa](https://github.com/zynqa)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
