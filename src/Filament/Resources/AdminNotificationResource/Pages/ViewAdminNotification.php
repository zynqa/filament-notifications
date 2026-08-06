<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\Pages;

use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource;

class ViewAdminNotification extends ViewRecord
{
    protected static string $resource = AdminNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Send Notification')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Send Notification')
                ->modalDescription(fn () => "Send this notification to {$this->record->recipients->count()} user(s)?")
                ->modalSubmitActionLabel('Send Notification')
                ->action(function () {
                    $count = $this->record->recipients->count();
                    $this->record->sendToRecipients();

                    Notification::make()
                        ->title('Notification Sent Successfully')
                        ->body("Sent to {$count} user(s)")
                        ->success()
                        ->send();

                    $this->refreshFormData(['sent_at']);
                })
                ->visible(fn (): bool => $this->record->isDraft()),

            EditAction::make()
                ->visible(fn (): bool => $this->record->isDraft()),

            DeleteAction::make()
                ->visible(fn (): bool => $this->record->isDraft()),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            // One column, explicitly. Filament 4 changed a view page's default schema to a
            // two-column grid, so these sections sat side by side where they had always
            // stacked. Each section already sets its own internal column count; the change
            // here is only to the arrangement of the sections themselves.
            ->columns(1)
            ->components([
                Section::make('Notification Details')
                    ->schema([
                        TextEntry::make('title')
                            ->size(TextSize::Large)
                            ->weight('bold'),

                        TextEntry::make('body')
                            ->markdown()
                            ->columnSpanFull(),

                        TextEntry::make('notification_type')
                            ->label('Type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'success' => 'success',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                default => 'info',
                            }),

                        TextEntry::make('icon')
                            ->formatStateUsing(fn (string $state): string => str_replace(['heroicon-o-', '-'], ['', ' '], $state))
                            ->badge(),

                        TextEntry::make('icon_color')
                            ->label('Icon Color')
                            ->badge()
                            ->color(fn (string $state): string => $state),

                        TextEntry::make('url')
                            ->label('Action URL')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->placeholder('No URL provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Status & Analytics')
                    ->schema([
                        TextEntry::make('sent_at')
                            ->label('Status')
                            ->formatStateUsing(fn ($state): string => $state ? 'Sent' : 'Draft')
                            ->badge()
                            ->color(fn ($state): string => $state ? 'success' : 'gray'),

                        TextEntry::make('recipients_count')
                            ->label('Total Recipients')
                            ->getStateUsing(fn () => $this->record->recipients()->count())
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('read_count')
                            ->label('Read')
                            ->getStateUsing(fn () => $this->record->readRecipients()->count())
                            ->badge()
                            ->color('success'),

                        TextEntry::make('unread_count')
                            ->label('Unread')
                            ->getStateUsing(fn () => $this->record->unreadRecipients()->count())
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('sent_at')
                            ->label('Sent At')
                            ->formatStateUsing(fn ($state): string => $state
                                ? Carbon::parse($state)->format(app(GeneralSettings::class)->date_format.' H:i')
                                : '—'
                            )
                            ->placeholder('Not sent yet'),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->formatStateUsing(fn ($state): string => $state
                                ? Carbon::parse($state)->format(app(GeneralSettings::class)->date_format.' H:i')
                                : '—'
                            ),
                    ])
                    ->columns(3),

                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('creator.name')
                            ->label('Created By'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->formatStateUsing(fn ($state): string => $state
                                ? Carbon::parse($state)->format(app(GeneralSettings::class)->date_format.' H:i')
                                : '—'
                            ),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
