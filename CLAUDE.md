# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

`zynqa/filament-notifications` is a FilamentPHP v3 plugin for creating and managing admin notifications with database and email delivery, read tracking, entity subscriptions, and Shield integration. It is installed as a Composer package inside a Laravel host app (`portal`).

**Namespace:** `Zynqa\FilamentNotifications\`
**Requires:** PHP 8.2+, Laravel 11+, Filament 3.2+, `spatie/laravel-package-tools`

## Development Commands

This is a library package — no standalone build/serve commands. It is developed in the context of the host app at `/Users/rdiaz/Sites/portal/`.

```bash
# Run host app tests (from host root via Warden)
warden env exec php-fpm vendor/bin/pest

# Lint with Pint (from host root)
warden env exec php-fpm vendor/bin/pint vendor/zynqa/filament-notifications/src

# Static analysis (from host root)
warden env exec php-fpm vendor/bin/phpstan analyse vendor/zynqa/filament-notifications/src --memory-limit=2G

# Run migrations after schema changes
warden env exec php-fpm php artisan migrate

# Publish config
warden env exec php-fpm php artisan vendor:publish --tag="filament-notifications-config"
```

## Architecture

### Plugin Registration Flow

1. **`FilamentNotificationsServiceProvider`** (extends `PackageServiceProvider`) — registers config, migrations, views, and the `SubscriptionNotificationService` singleton. On boot, creates the default email template, registers the `NotificationReadSync` Livewire component, and ensures settings exist in DB.
2. **`FilamentNotificationsPlugin`** (implements `Filament\Contracts\Plugin`) — registered in the host panel provider. Registers `AdminNotificationResource`, `MySubscriptions` page, and `ManageEntityTypeSettings` page. On boot, registers the `AdminNotificationPolicy` via Gate and injects the `NotificationReadSync` Livewire component at `BODY_END`.

### Two Notification Systems

The package provides two independent notification mechanisms:

1. **Admin Notifications** — Manual notifications created by admins via the Filament resource. Supports draft/send workflow, database bell and/or email delivery, recipient selection, and read tracking via `notification_recipients` pivot table.

2. **Entity Subscriptions** — Automated notifications triggered when subscribed entities change. Models implement `Subscribable` contract and use `HasSubscribers` trait; the User model uses `HasSubscriptions` trait. `SubscriptionNotificationService::notifySubscribersOf()` dispatches notifications to all subscribers of an entity.

### Key Models

- **`AdminNotification`** — Central model with soft deletes. Tracks `sent_at` (null = draft). Has `recipients()` BelongsToMany via `notification_recipients` pivot with `read_at`. Static `createFromSystem()` factory for programmatic system notifications.
- **`EntitySubscription`** — Polymorphic subscription (user → subscribable entity) with `channel` field (database/email/both).
- **`EntityTypeSetting`** — Per-entity-type configuration seeded from plugin registration.

### Notification Delivery

- **`AdminBroadcastNotification`** — Laravel Notification class. Channels determined by `delivery_method` (database/email/both). Database channel uses Filament's `Notification` builder. Email uses configurable Blade templates from `storage/app/mail-templates/`.
- **`EntitySubscriptionNotification`** — Dispatched by `SubscriptionNotificationService` for subscription-based notifications.

### Read Tracking Sync

`NotificationReadSync` Livewire component (injected at body end) bridges Filament's native `DatabaseNotification.read_at` with the package's `notification_recipients.read_at` pivot field, since Filament's raw query bypass doesn't trigger model events.

### Settings

Uses `spatie/laravel-settings` — `NotificationSettings` class in group `filament-notifications`. Migration in `database/settings/`. The service provider ensures the setting row exists on boot.

### Database Tables

| Table | Purpose |
|---|---|
| `admin_notifications` | Notification content, metadata, sent_at |
| `notification_recipients` | Pivot: admin_notification ↔ user with read_at |
| `entity_subscriptions` | Polymorphic subscription records |
| `entity_type_settings` | Per-entity-type configuration |
| `settings` (shared) | Spatie settings for email template preference |

Migrations use both `.php.stub` (publishable) and `.php` (auto-loaded) formats.

### Subscribable Entity Integration

To make a host model subscribable:
1. Implement `Zynqa\FilamentNotifications\Contracts\Subscribable` on the model
2. Use `HasSubscribers` trait on the model
3. Use `HasSubscriptions` trait on the User model
4. Register via `FilamentNotificationsPlugin::registerSubscribableEntity()` in the panel provider

### Config

`config/filament-notifications.php` controls navigation group/sort/icon, Shield permission prefix, and available heroicon options.

## Code Style

- All files use `declare(strict_types=1)`
- Follows Laravel Pint formatting
- User model is resolved dynamically via `config('auth.providers.users.model')` — never hardcoded
