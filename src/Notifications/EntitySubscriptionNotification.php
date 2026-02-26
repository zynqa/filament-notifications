<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Notifications;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Zynqa\FilamentNotifications\Contracts\Subscribable;
use Zynqa\FilamentNotifications\Models\EntityTypeSetting;
use Zynqa\FilamentNotifications\Services\MailTemplateService;
use Zynqa\FilamentNotifications\Settings\NotificationSettings;

class EntitySubscriptionNotification extends Notification
{
    public function __construct(
        public readonly Subscribable $entity,
        public readonly string $event,
        public readonly array $context = [],
        public readonly string $channel = 'database',
    ) {}

    public function via(object $notifiable): array
    {
        return match ($this->channel) {
            'database' => ['database'],
            'email' => ['mail'],
            'both' => ['database', 'mail'],
            default => ['database'],
        };
    }

    public function toDatabase(object $notifiable): array
    {
        $actions = [];

        $url = $this->entity->getSubscribableUrl();
        if ($url) {
            $actions[] = Action::make('view')
                ->label('View')
                ->url($url)
                ->markAsRead();
        }

        $actions[] = Action::make('mark_as_read')
            ->label('Mark as Read')
            ->markAsRead();

        $notification = FilamentNotification::make()
            ->info()
            ->title($this->entity->getSubscribableLabel().': '.$this->event)
            ->body($this->buildBody())
            ->actions($actions)
            ->persistent();

        return $notification->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $templateName = EntityTypeSetting::getTemplateFor($this->entity::getSubscribableType());

        if (! $templateName) {
            try {
                $settings = app(NotificationSettings::class);
                $templateName = $settings->default_email_template;
            } catch (\Throwable) {
                $templateName = 'default.blade.php';
            }
        }

        if (! MailTemplateService::templateExists($templateName)) {
            $templateName = 'default.blade.php';
        }

        return (new MailMessage)
            ->subject($this->entity->getSubscribableLabel().': '.$this->event)
            ->view('filament-notifications::emails.notification', [
                'title' => $this->entity->getSubscribableLabel().': '.$this->event,
                'body' => $this->buildBody(),
                'url' => $this->entity->getSubscribableUrl(),
                'notification_type' => 'info',
                'icon' => 'heroicon-o-bell',
                'icon_color' => 'info',
                'templateName' => $templateName,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'entity_type' => $this->entity::getSubscribableType(),
            'entity_id' => $this->entity->getKey(),
            'event' => $this->event,
            'context' => $this->context,
        ];
    }

    private function buildBody(): string
    {
        if (empty($this->context)) {
            return '';
        }

        $lines = [];
        foreach ($this->context as $key => $value) {
            $lines[] = "{$key}: {$value}";
        }

        return implode(' | ', $lines);
    }
}
