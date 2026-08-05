<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Resources;

use App\Models\User;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\Pages\CreateAdminNotification;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\Pages\EditAdminNotification;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\Pages\ListAdminNotifications;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\Pages\ViewAdminNotification;
use Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\RelationManagers\RecipientsRelationManager;
use Zynqa\FilamentNotifications\Models\AdminNotification;

class AdminNotificationResource extends Resource
{
    protected static ?string $model = AdminNotification::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

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

    public static function form(Schema $schema): Schema
    {
        $userModel = Config::get('auth.providers.users.model', User::class);

        return $schema
            ->components([
                Section::make('Notification Content')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('body')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),

                        TextInput::make('url')
                            ->label('Action URL (Optional)')
                            ->url()
                            ->placeholder('https://example.com/page')
                            ->helperText('Add a clickable link that recipients can visit')
                            ->columnSpanFull(),
                    ]),

                Section::make('Delivery Options')
                    ->description('Choose how this notification will be delivered to recipients.')
                    ->schema([
                        Radio::make('delivery_method')
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

                Section::make('Notification Appearance')
                    ->schema([
                        Select::make('notification_type')
                            ->label('Type')
                            ->options(AdminNotification::getNotificationTypeOptions())
                            ->default('info')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (string $state, Set $set) => $set('icon_color', $state)),

                        Select::make('icon')
                            ->options(Config::get('filament-notifications.default_icons', []))
                            ->default('heroicon-o-bell')
                            ->searchable()
                            ->required()
                            ->native(false),

                        Select::make('icon_color')
                            ->label('Icon Color')
                            ->options(AdminNotification::getIconColorOptions())
                            ->default('info')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),

                Section::make('Recipients')
                    ->schema([
                        Select::make('recipient_user_ids')
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
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(20),

                BadgeColumn::make('notification_type')
                    ->label('Type')
                    ->colors([
                        'info' => 'info',
                        'success' => 'success',
                        'warning' => 'warning',
                        'danger' => 'danger',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                IconColumn::make('icon')
                    ->icon(fn (AdminNotification $record): string => $record->icon),

                TextColumn::make('recipients_count')
                    ->label('Recipients')
                    ->counts('recipients')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('read_count')
                    ->label('Read')
                    ->getStateUsing(fn (AdminNotification $record): int => $record->readRecipients()->count())
                    ->badge()
                    ->color('success'),

                TextColumn::make('unread_count')
                    ->label('Unread')
                    ->getStateUsing(fn (AdminNotification $record): int => $record->unreadRecipients()->count())
                    ->badge()
                    ->color('warning'),

                BadgeColumn::make('delivery_method')
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

                IconColumn::make('sent_at')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-airplane')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (AdminNotification $record): string => $record->isSent() ? 'Sent' : 'Draft'),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->formatStateUsing(fn ($state, AdminNotification $record): string => $record->source === 'system' ? 'System' : ($state ?? '—'))
                    ->badge()
                    ->color(fn (AdminNotification $record): string => $record->source === 'system' ? 'gray' : 'primary')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->formatStateUsing(fn ($state): string => $state
                        ? Carbon::parse($state)->format(app(GeneralSettings::class)->date_format.' H:i')
                        : '—'
                    )
                    ->sortable()
                    ->placeholder('Not sent yet'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('notification_type')
                    ->label('Type')
                    ->options(AdminNotification::getNotificationTypeOptions()),

                TernaryFilter::make('sent_at')
                    ->label('Status')
                    ->placeholder('All Notifications')
                    ->trueLabel('Sent')
                    ->falseLabel('Drafts')
                    ->queries(
                        true: fn (Builder $query) => $query->sent(),
                        false: fn (Builder $query) => $query->draft(),
                    ),

                Filter::make('created_by_me')
                    ->label('Created by Me')
                    ->query(fn (Builder $query): Builder => $query->where('created_by', Auth::id())),
            ])
            ->recordActions([
                Action::make('send')
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

                ViewAction::make(),

                EditAction::make()
                    ->visible(fn (AdminNotification $record): bool => $record->isDraft()),

                DeleteAction::make()
                    ->visible(fn (AdminNotification $record): bool => $record->isDraft()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminNotifications::route('/'),
            'create' => CreateAdminNotification::route('/create'),
            'view' => ViewAdminNotification::route('/{record}'),
            'edit' => EditAdminNotification::route('/{record}/edit'),
        ];
    }
}
