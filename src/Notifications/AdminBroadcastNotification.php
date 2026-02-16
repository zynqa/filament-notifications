<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Notifications;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;
use Zynqa\FilamentNotifications\Models\AdminNotification;

class AdminBroadcastNotification extends Notification
{

    public function __construct(
        public AdminNotification $adminNotification
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        // Build Filament notification based on type
        $filamentNotification = FilamentNotification::make();

        // Set notification type
        switch ($this->adminNotification->notification_type) {
            case 'success':
                $filamentNotification->success();
                break;
            case 'warning':
                $filamentNotification->warning();
                break;
            case 'danger':
                $filamentNotification->danger();
                break;
            default:
                $filamentNotification->info();
                break;
        }

        // Build actions array
        $actions = [];

        // Add URL action if URL is provided
        if (! empty($this->adminNotification->url)) {
            $actions[] = Action::make('view')
                ->label('View')
                ->url($this->adminNotification->url)
                ->markAsRead();
        }

        // Always add Mark as Read action
        $actions[] = Action::make('mark_as_read')
            ->label('Mark as Read')
            ->markAsRead()
            ->close();

        // Configure the notification
        $filamentNotification
            ->icon($this->adminNotification->icon)
            ->iconColor($this->adminNotification->icon_color)
            ->title($this->adminNotification->title)
            ->body($this->adminNotification->body)
            ->actions($actions)
            ->persistent();

        return $filamentNotification->getDatabaseMessage();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'admin_notification_id' => $this->adminNotification->id,
            'title' => $this->adminNotification->title,
            'body' => $this->adminNotification->body,
            'type' => $this->adminNotification->notification_type,
            'icon' => $this->adminNotification->icon,
            'icon_color' => $this->adminNotification->icon_color,
            'url' => $this->adminNotification->url,
        ];
    }
}
