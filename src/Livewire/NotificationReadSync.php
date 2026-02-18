<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Livewire;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationReadSync extends Component
{
    #[On('markedNotificationAsRead')]
    public function syncRead(string $id): void
    {
        $notification = DatabaseNotification::find($id);

        if (! $notification) {
            return;
        }

        $adminNotificationId = $notification->data['admin_notification_id'] ?? null;

        if (! $adminNotificationId) {
            return;
        }

        DB::table('notification_recipients')
            ->where('admin_notification_id', $adminNotificationId)
            ->where('user_id', $notification->notifiable_id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
