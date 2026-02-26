<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Zynqa\FilamentNotifications\Models\EntitySubscription;

trait HasSubscribers
{
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(EntitySubscription::class, 'subscribable');
    }

    public function subscribers(): MorphToMany
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        return $this->morphToMany($userModel, 'subscribable', 'entity_subscriptions')
            ->withPivot('channel', 'subscribed_at')
            ->withTimestamps();
    }
}
