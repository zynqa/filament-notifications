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

        $type = $entity::getSubscribableType();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;

            if (! $user) {
                continue;
            }

            // The user's per-type preference is authoritative over the per-subscription
            // channel. Fall back to the subscription channel if the host user model does
            // not expose preferences (keeps the package usable without the trait).
            $channel = method_exists($user, 'notificationChannelFor')
                ? $user->notificationChannelFor($type)
                : $subscription->channel;

            // 'off' mutes this notification type entirely for the user.
            if ($channel === 'off') {
                continue;
            }

            try {
                $user->notify(new EntitySubscriptionNotification(
                    entity: $entity,
                    event: $event,
                    context: $context,
                    channel: $channel,
                ));

                AdminNotification::createFromSystem(
                    title: $entity->getSubscribableLabel().': '.$event,
                    body: $this->buildBody($context),
                    notificationType: 'info',
                    icon: 'heroicon-o-bell',
                    iconColor: 'info',
                    url: $entity->getSubscribableUrl(),
                    recipientIds: $user->id,
                    deliveryMethod: $channel,
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
