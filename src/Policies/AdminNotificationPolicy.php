<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Config;
use Zynqa\FilamentNotifications\Models\AdminNotification;

class AdminNotificationPolicy
{
    use HandlesAuthorization;

    /**
     * Allow super_admin to bypass all checks (mirrors Shield-generated policy behaviour
     * when filament-shield.super_admin.define_via_gate is false).
     */
    public function before($user, string $ability): ?bool
    {
        $superAdminRole = Config::get('filament-shield.super_admin.name', 'super_admin');

        if (method_exists($user, 'hasRole') && $user->hasRole($superAdminRole)) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        if (! Config::get('filament-notifications.permissions.enabled', true)) {
            return true;
        }

        $prefix = Config::get('filament-notifications.permissions.prefix', 'admin::notification');

        return $user->can("view_any_{$prefix}");
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, AdminNotification $adminNotification): bool
    {
        if (! Config::get('filament-notifications.permissions.enabled', true)) {
            return true;
        }

        $prefix = Config::get('filament-notifications.permissions.prefix', 'admin::notification');

        return $user->can("view_{$prefix}");
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        if (! Config::get('filament-notifications.permissions.enabled', true)) {
            return true;
        }

        $prefix = Config::get('filament-notifications.permissions.prefix', 'admin::notification');

        return $user->can("create_{$prefix}");
    }

    /**
     * Determine whether the user can update the model.
     * IMPORTANT: Only drafts can be edited, sent notifications are read-only.
     */
    public function update($user, AdminNotification $adminNotification): bool
    {
        // Cannot edit sent notifications
        if ($adminNotification->isSent()) {
            return false;
        }

        if (! Config::get('filament-notifications.permissions.enabled', true)) {
            return true;
        }

        $prefix = Config::get('filament-notifications.permissions.prefix', 'admin::notification');

        return $user->can("update_{$prefix}");
    }

    /**
     * Determine whether the user can delete the model.
     * IMPORTANT: Only drafts can be deleted.
     */
    public function delete($user, AdminNotification $adminNotification): bool
    {
        // Cannot delete sent notifications
        if ($adminNotification->isSent()) {
            return false;
        }

        if (! Config::get('filament-notifications.permissions.enabled', true)) {
            return true;
        }

        $prefix = Config::get('filament-notifications.permissions.prefix', 'admin::notification');

        return $user->can("delete_{$prefix}");
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, AdminNotification $adminNotification): bool
    {
        if (! Config::get('filament-notifications.permissions.enabled', true)) {
            return true;
        }

        $prefix = Config::get('filament-notifications.permissions.prefix', 'admin::notification');

        return $user->can("restore_{$prefix}");
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, AdminNotification $adminNotification): bool
    {
        if (! Config::get('filament-notifications.permissions.enabled', true)) {
            return true;
        }

        $prefix = Config::get('filament-notifications.permissions.prefix', 'admin::notification');

        return $user->can("force_delete_{$prefix}");
    }
}
