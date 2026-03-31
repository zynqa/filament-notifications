# Repository Guidelines

## Project Structure & Module Organization
This package is a Laravel/Filament library under `src/` with PSR-4 namespace `Zynqa\\FilamentNotifications\\`. Core entry points live in `src/FilamentNotificationsServiceProvider.php` and `src/FilamentNotificationsPlugin.php`. Keep domain logic in `src/Services/`, Eloquent models in `src/Models/`, policies in `src/Policies/`, Filament resources/pages in `src/Filament/`, and notification classes in `src/Notifications/`. Package config lives in `config/filament-notifications.php`; publishable migrations are in `database/migrations/` and `database/settings/`; Blade views and stubs live in `resources/views/` and `resources/stubs/`.

## Build, Test, and Development Commands
This repository does not define Composer scripts or a standalone test harness. Use the host Laravel app to validate changes:

- `composer dump-autoload` refreshes package autoloading after class or namespace changes.
- `php artisan migrate` verifies package migrations load correctly in the consuming app.
- `php artisan optimize:clear` clears cached config, views, and routes after changing config, views, or service-provider wiring.
- `php artisan test` runs host-application tests that exercise this package, if the consumer app includes coverage.

## Coding Style & Naming Conventions
Follow the existing PHP style: `declare(strict_types=1);`, 4-space indentation, typed return values, and short docblocks only where intent is not obvious. Match Laravel naming conventions: singular model names like `AdminNotification`, descriptive service classes like `SubscriptionNotificationService`, and Filament page/resource classes grouped by feature. Use kebab-case for config keys and migration filenames, and Blade/stub filenames such as `notification.blade.php` or `default-email-template.blade.php.stub`.

## Testing Guidelines
There is currently no `tests/` directory in this package. When adding behavior, validate it through a local Laravel app that installs the package and cover both database and Filament flows where possible. If you add tests later, mirror Laravel conventions with feature-style names such as `AdminNotificationTest.php` and keep package fixtures minimal.

## Commit & Pull Request Guidelines
Recent history uses short, imperative, sentence-style subjects such as `Adds entity subscription management with notification handling and settings configuration`. Keep commits focused on one change. Pull requests should include a concise summary, note any migration or config impact, link the related ticket, and attach screenshots for Filament UI changes.

## Configuration Notes
Do not hardcode app-specific models or permissions. Prefer config-driven lookups, safe defaults, and package-level migrations so the consuming application controls integration details.
