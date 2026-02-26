<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Services;

use Illuminate\Support\Facades\Log;
use Zynqa\FilamentNotifications\Contracts\Subscribable;
use Zynqa\FilamentNotifications\Models\AdminNotification;
use Zynqa\FilamentNotifications\Models\EntitySubscription;
use Zynqa\FilamentNotifications\Notifications\EntitySubscriptionNotification;

class SubscriptionNotificationService
{
    public function notifySubscribersOf(Subscribable $entity, string $event, array $context = []): void
    {
        $subscriptions = EntitySubscription::query()
            ->where('subscribable_type', $entity::getSubscribableType())
            ->where('subscribable_id', $entity->getKey())
            ->with('user')
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;

            if (! $user) {
                continue;
            }

            try {
                $user->notify(new EntitySubscriptionNotification(
                    entity: $entity,
                    event: $event,
                    context: $context,
                    channel: $subscription->channel,
                ));

                AdminNotification::createFromSystem(
                    title: $entity->getSubscribableLabel().': '.$event,
                    body: $this->buildBody($context),
                    notificationType: 'info',
                    icon: 'heroicon-o-bell',
                    iconColor: 'info',
                    url: $entity->getSubscribableUrl(),
                    recipientIds: $user->id,
                    deliveryMethod: $subscription->channel,
                );
            } catch (\Throwable $e) {
                Log::error('Failed to notify subscriber', [
                    'user_id' => $user->id,
                    'entity_type' => $entity::getSubscribableType(),
                    'entity_id' => $entity->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function buildBody(array $context): string
    {
        if (empty($context)) {
            return '';
        }

        $lines = [];
        foreach ($context as $key => $value) {
            $lines[] = "{$key}: {$value}";
        }

        return implode(' | ', $lines);
    }
}
