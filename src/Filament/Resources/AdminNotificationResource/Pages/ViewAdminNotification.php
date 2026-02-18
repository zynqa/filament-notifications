<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\Pages;

use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource;

class ViewAdminNotification extends ViewRecord
{
    protected static string $resource = AdminNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send')
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

            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->isDraft()),

            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record->isDraft()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Notification Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('body')
                            ->markdown()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('notification_type')
                            ->label('Type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'success' => 'success',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                default => 'info',
                            }),

                        Infolists\Components\TextEntry::make('icon')
                            ->formatStateUsing(fn (string $state): string => str_replace(['heroicon-o-', '-'], ['', ' '], $state))
                            ->badge(),

                        Infolists\Components\TextEntry::make('icon_color')
                            ->label('Icon Color')
                            ->badge()
                            ->color(fn (string $state): string => $state),

                        Infolists\Components\TextEntry::make('url')
                            ->label('Action URL')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->placeholder('No URL provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Status & Analytics')
                    ->schema([
                        Infolists\Components\TextEntry::make('sent_at')
                            ->label('Status')
                            ->formatStateUsing(fn ($state): string => $state ? 'Sent' : 'Draft')
                            ->badge()
                            ->color(fn ($state): string => $state ? 'success' : 'gray'),

                        Infolists\Components\TextEntry::make('recipients_count')
                            ->label('Total Recipients')
                            ->getStateUsing(fn () => $this->record->recipients()->count())
                            ->badge()
                            ->color('gray'),

                        Infolists\Components\TextEntry::make('read_count')
                            ->label('Read')
                            ->getStateUsing(fn () => $this->record->readRecipients()->count())
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('unread_count')
                            ->label('Unread')
                            ->getStateUsing(fn () => $this->record->unreadRecipients()->count())
                            ->badge()
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('sent_at')
                            ->label('Sent At')
                            ->formatStateUsing(fn ($state): string => $state
                                ? \Carbon\Carbon::parse($state)->format(app(\App\Settings\GeneralSettings::class)->date_format . ' H:i')
                                : '—'
                            )
                            ->placeholder('Not sent yet'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->formatStateUsing(fn ($state): string => $state
                                ? \Carbon\Carbon::parse($state)->format(app(\App\Settings\GeneralSettings::class)->date_format . ' H:i')
                                : '—'
                            ),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Created By'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->formatStateUsing(fn ($state): string => $state
                                ? \Carbon\Carbon::parse($state)->format(app(\App\Settings\GeneralSettings::class)->date_format . ' H:i')
                                : '—'
                            ),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
