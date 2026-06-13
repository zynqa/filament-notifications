<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Concerns;

use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Zynqa\FilamentNotifications\Support\NotificationChannelResolver;

/**
 * Drop-in for a Filament profile/account page (e.g. an EditProfile subclass) that adds a
 * per-notification-type channel preferences section.
 *
 * The host page must expose `getUser()` (Filament's EditProfile does) and a User model
 * using the HasSubscriptions trait. Wire it up as:
 *   - form():                    append ...$this->getNotificationPreferenceFormComponents()
 *   - mutateFormDataBeforeFill(): return $this->fillNotificationPreferences($data)
 *   - mutateFormDataBeforeSave(): return $this->persistNotificationPreferences($data)
 */
trait ManagesNotificationPreferences
{
    protected function notificationPreferenceStateKey(string $alias): string
    {
        return "notification_preference_{$alias}";
    }

    /**
     * Registered subscribable notification types, keyed by alias.
     *
     * @return array<string, array{class: string, label: string, defaultChannel: string}>
     */
    protected function getSubscribableNotificationTypes(): array
    {
        try {
            return Filament::getCurrentPanel()
                ->getPlugin('filament-notifications')
                ->getRegisteredEntityTypes();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * The raw channel Select fields, one per registered subscribable type. Empty when no
     * types are registered. Use this when placing the preferences inside your own layout
     * (e.g. a Tab); use getNotificationPreferenceFormComponents() for a ready-made Section.
     *
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected function getNotificationPreferenceFields(): array
    {
        $fields = [];

        foreach ($this->getSubscribableNotificationTypes() as $alias => $config) {
            $fields[] = Select::make($this->notificationPreferenceStateKey($alias))
                ->label($config['label'])
                ->options(NotificationChannelResolver::options())
                ->default(NotificationChannelResolver::DEFAULT)
                ->selectablePlaceholder(false)
                ->native(false)
                ->required();
        }

        return $fields;
    }

    /**
     * Form components for the notification preferences section. Empty when no
     * subscribable types are registered (so it cleanly disappears).
     *
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected function getNotificationPreferenceFormComponents(): array
    {
        $fields = $this->getNotificationPreferenceFields();

        if ($fields === []) {
            return [];
        }

        return [
            Section::make(__('Notification preferences'))
                ->description(__('Choose how you want to be notified about each type of update.'))
                ->schema($fields),
        ];
    }

    /**
     * Inject the user's current preferences into the form state.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillNotificationPreferences(array $data): array
    {
        $user = $this->getUser();

        foreach (array_keys($this->getSubscribableNotificationTypes()) as $alias) {
            $data[$this->notificationPreferenceStateKey($alias)] = $user->notificationChannelFor($alias);
        }

        return $data;
    }

    /**
     * Persist preference selections and strip them from the data so the user record
     * update does not receive unknown attributes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function persistNotificationPreferences(array $data): array
    {
        $user = $this->getUser();

        foreach (array_keys($this->getSubscribableNotificationTypes()) as $alias) {
            $key = $this->notificationPreferenceStateKey($alias);

            if (array_key_exists($key, $data)) {
                $user->setNotificationChannelFor($alias, (string) $data[$key]);
                unset($data[$key]);
            }
        }

        return $data;
    }
}
