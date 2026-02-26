<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\Pages;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\RelationManagers;
use Zynqa\FilamentNotifications\Models\AdminNotification;
use Zynqa\FilamentNotifications\Notifications\AdminBroadcastNotification;

class AdminNotificationResource extends Resource
{
    protected static ?string $model = AdminNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $modelLabel = 'Notification';

    protected static ?string $pluralModelLabel = 'Notifications';

    public static function getNavigationGroup(): ?string
    {
        return Config::get('filament-notifications.navigation.group', 'Users & Roles');
    }

    public static function getNavigationSort(): ?int
    {
        return Config::get('filament-notifications.navigation.sort', 3);
    }

    public static function form(Form $form): Form
    {
        $userModel = Config::get('auth.providers.users.model', \App\Models\User::class);

        return $form
            ->schema([
                Forms\Components\Section::make('Notification Content')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('body')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('url')
                            ->label('Action URL (Optional)')
                            ->url()
                            ->placeholder('https://example.com/page')
                            ->helperText('Add a clickable link that recipients can visit')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Delivery Options')
                    ->description('Choose how this notification will be delivered to recipients.')
                    ->schema([
                        Forms\Components\Radio::make('delivery_method')
                            ->label('How should this notification be delivered?')
                            ->options([
                                'database' => 'Database Notification Only',
                                'email' => 'Email Only',
                                'both' => 'Both Database and Email',
                            ])
                            ->descriptions([
                                'database' => 'Recipients will see this notification in their Filament notification bell (current default behavior)',
                                'email' => 'Recipients will receive an email notification using the template configured in settings',
                                'both' => 'Recipients will receive both a database notification and an email notification',
                            ])
                            ->default('database')
                            ->required()
                            ->inline(false)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->hidden(fn (?AdminNotification $record) => $record?->isSent() ?? false),

                Forms\Components\Section::make('Notification Appearance')
                    ->schema([
                        Forms\Components\Select::make('notification_type')
                            ->label('Type')
                            ->options(AdminNotification::getNotificationTypeOptions())
                            ->default('info')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (string $state, Forms\Set $set) => $set('icon_color', $state)),

                        Forms\Components\Select::make('icon')
                            ->options(Config::get('filament-notifications.default_icons', []))
                            ->default('heroicon-o-bell')
                            ->searchable()
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('icon_color')
                            ->label('Icon Color')
                            ->options(AdminNotification::getIconColorOptions())
                            ->default('info')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Recipients')
                    ->schema([
                        Forms\Components\Select::make('recipient_user_ids')
                            ->label('Select Users to Notify')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => $userModel::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->required()
                            ->helperText('Select one or more users who will receive this notification')
                            ->columnSpanFull(),
                    ])
                    ->hidden(fn (?AdminNotification $record) => $record?->isSent() ?? false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(20),

                Tables\Columns\BadgeColumn::make('notification_type')
                    ->label('Type')
                    ->colors([
                        'info' => 'info',
                        'success' => 'success',
                        'warning' => 'warning',
                        'danger' => 'danger',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\IconColumn::make('icon')
                    ->icon(fn (AdminNotification $record): string => $record->icon),

                Tables\Columns\TextColumn::make('recipients_count')
                    ->label('Recipients')
                    ->counts('recipients')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('read_count')
                    ->label('Read')
                    ->getStateUsing(fn (AdminNotification $record): int => $record->readRecipients()->count())
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('unread_count')
                    ->label('Unread')
                    ->getStateUsing(fn (AdminNotification $record): int => $record->unreadRecipients()->count())
                    ->badge()
                    ->color('warning'),

                Tables\Columns\BadgeColumn::make('delivery_method')
                    ->label('Delivery')
                    ->colors([
                        'gray' => 'database',
                        'info' => 'email',
                        'success' => 'both',
                    ])
                    ->icons([
                        'heroicon-o-bell' => 'database',
                        'heroicon-o-envelope' => 'email',
                        'heroicon-o-bell-alert' => 'both',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'database' => 'Database',
                        'email' => 'Email',
                        'both' => 'Both',
                        default => ucfirst($state),
                    }),

                Tables\Columns\IconColumn::make('sent_at')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-airplane')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (AdminNotification $record): string => $record->isSent() ? 'Sent' : 'Draft'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->formatStateUsing(fn ($state, AdminNotification $record): string => $record->source === 'system' ? 'System' : ($state ?? '—'))
                    ->badge()
                    ->color(fn (AdminNotification $record): string => $record->source === 'system' ? 'gray' : 'primary')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->formatStateUsing(fn ($state): string => $state
                        ? \Carbon\Carbon::parse($state)->format(app(\App\Settings\GeneralSettings::class)->date_format . ' H:i')
                        : '—'
                    )
                    ->sortable()
                    ->placeholder('Not sent yet'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('notification_type')
                    ->label('Type')
                    ->options(AdminNotification::getNotificationTypeOptions()),

                Tables\Filters\TernaryFilter::make('sent_at')
                    ->label('Status')
                    ->placeholder('All Notifications')
                    ->trueLabel('Sent')
                    ->falseLabel('Drafts')
                    ->queries(
                        true: fn (Builder $query) => $query->sent(),
                        false: fn (Builder $query) => $query->draft(),
                    ),

                Tables\Filters\Filter::make('created_by_me')
                    ->label('Created by Me')
                    ->query(fn (Builder $query): Builder => $query->where('created_by', Auth::id())),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Notification')
                    ->modalDescription(fn (AdminNotification $record) => "Send this notification to {$record->recipients->count()} user(s)?")
                    ->modalSubmitActionLabel('Send Notification')
                    ->action(function (AdminNotification $record) {
                        $count = $record->recipients->count();
                        $record->sendToRecipients();

                        Notification::make()
                            ->title('Notification Sent Successfully')
                            ->body("Sent to {$count} user(s)")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (AdminNotification $record): bool => $record->isDraft()),

                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (AdminNotification $record): bool => $record->isDraft()),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (AdminNotification $record): bool => $record->isDraft()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminNotifications::route('/'),
            'create' => Pages\CreateAdminNotification::route('/create'),
            'view' => Pages\ViewAdminNotification::route('/{record}'),
            'edit' => Pages\EditAdminNotification::route('/{record}/edit'),
        ];
    }
}
