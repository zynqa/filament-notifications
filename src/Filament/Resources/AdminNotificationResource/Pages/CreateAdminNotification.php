<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource;

class CreateAdminNotification extends CreateRecord
{
    protected static string $resource = AdminNotificationResource::class;

    protected array $recipientIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract recipient IDs (will be attached in afterCreate)
        $this->recipientIds = $data['recipient_user_ids'] ?? [];
        unset($data['recipient_user_ids']);

        // Set created_by to current user
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // Attach recipients to the notification
        if (! empty($this->recipientIds)) {
            $this->record->recipients()->attach($this->recipientIds);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
