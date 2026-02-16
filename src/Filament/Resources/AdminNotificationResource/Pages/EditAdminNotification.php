<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource;

class EditAdminNotification extends EditRecord
{
    protected static string $resource = AdminNotificationResource::class;

    protected array $recipientIds = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing recipients into the form
        $data['recipient_user_ids'] = $this->record->recipients()->pluck('users.id')->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract recipient IDs (will be synced in afterSave)
        $this->recipientIds = $data['recipient_user_ids'] ?? [];
        unset($data['recipient_user_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Sync recipients (only for drafts)
        if ($this->record->isDraft()) {
            $this->record->recipients()->sync($this->recipientIds);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
