<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Zynqa\FilamentNotifications\Contracts\Subscribable;
use Zynqa\FilamentNotifications\Models\EntitySubscription;

trait HasSubscriptions
{
    public function entitySubscriptions(): HasMany
    {
        return $this->hasMany(EntitySubscription::class);
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
