<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Zynqa\FilamentNotifications\Contracts\Subscribable;
use Zynqa\FilamentNotifications\Models\EntitySubscription;
use Zynqa\FilamentNotifications\Models\NotificationPreference;
use Zynqa\FilamentNotifications\Support\NotificationChannelResolver;

trait HasSubscriptions
{
    public function entitySubscriptions(): HasMany
    {
        return $this->hasMany(EntitySubscription::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * Resolve the effective notification channel for a given notification type,
     * falling back to the historical default (`database`) when unset.
     */
    public function notificationChannelFor(string $type): string
    {
        $stored = $this->notificationPreferences()
            ->where('notification_type', $type)
            ->value('channel');

        return NotificationChannelResolver::resolve($stored);
    }

    /**
     * Persist the user's channel preference for a notification type.
     * Invalid values fall back to the default channel.
     */
    public function setNotificationChannelFor(string $type, string $channel): NotificationPreference
    {
        $channel = NotificationChannelResolver::resolve($channel);

        return $this->notificationPreferences()->updateOrCreate(
            ['notification_type' => $type],
            ['channel' => $channel],
        );
    }

    public function isSubscribedTo(Subscribable $entity): bool
    {
        return $this->entitySubscriptions()
            ->where('subscribable_type', $entity::getSubscribableType())
            ->where('subscribable_id', $entity->getKey())
            ->exists();
    }

    public function subscribeTo(Subscribable $entity, string $channel = 'database'): EntitySubscription
    {
        return $this->entitySubscriptions()->firstOrCreate(
            [
                'subscribable_type' => $entity::getSubscribableType(),
                'subscribable_id' => $entity->getKey(),
            ],
            [
                'channel' => $channel,
                'subscribed_at' => now(),
            ]
        );
    }

    public function unsubscribeFrom(Subscribable $entity): void
    {
        $this->entitySubscriptions()
            ->where('subscribable_type', $entity::getSubscribableType())
            ->where('subscribable_id', $entity->getKey())
            ->delete();
    }
}
