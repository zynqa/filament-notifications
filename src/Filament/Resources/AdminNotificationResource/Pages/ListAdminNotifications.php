<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource;

class ListAdminNotifications extends ListRecords
{
    protected static string $resource = AdminNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
