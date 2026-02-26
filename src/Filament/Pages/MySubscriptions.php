<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Zynqa\FilamentNotifications\FilamentNotificationsPlugin;
use Zynqa\FilamentNotifications\Models\EntitySubscription;

class MySubscriptions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament-notifications::pages.my-subscriptions';

    protected static ?string $title = 'My Subscriptions';

    protected static ?string $navigationLabel = 'My Subscriptions';

    public static function getNavigationGroup(): ?string
    {
        try {
            return FilamentNotificationsPlugin::get()->getMySubscriptionsNavigationGroup();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationSort(): ?int
    {
        try {
            return FilamentNotificationsPlugin::get()->getMySubscriptionsNavigationSort();
        } catch (\Throwable) {
            return null;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EntitySubscription::query()
                    ->where('user_id', Auth::id())
                    ->with('subscribable')
            )
            ->columns([
                TextColumn::make('subscribable_type')
                    ->label('Entity Type')
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->sortable(),
                TextColumn::make('label')
                    ->label('Item')
                    ->getStateUsing(function (EntitySubscription $record): string {
                        $subscribable = $record->subscribable;
                        if ($subscribable && method_exists($subscribable, 'getSubscribableLabel')) {
                            return $subscribable->getSubscribableLabel();
                        }

                        return "{$record->subscribable_type} #{$record->subscribable_id}";
                    }),
                BadgeColumn::make('channel')
                    ->label('Channel')
                    ->colors([
                        'info' => 'database',
                        'warning' => 'email',
                        'success' => 'both',
                    ])
                    ->sortable(),
                TextColumn::make('subscribed_at')
                    ->label('Subscribed')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('View Item')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(function (EntitySubscription $record): ?string {
                        $subscribable = $record->subscribable;
                        if ($subscribable && method_exists($subscribable, 'getSubscribableUrl')) {
                            return $subscribable->getSubscribableUrl();
                        }

                        return null;
                    })
                    ->openUrlInNewTab()
                    ->visible(function (EntitySubscription $record): bool {
                        $subscribable = $record->subscribable;

                        return $subscribable !== null && method_exists($subscribable, 'getSubscribableUrl') && $subscribable->getSubscribableUrl() !== null;
                    }),
                TableAction::make('unsubscribe')
                    ->label('Unsubscribe')
                    ->icon('heroicon-o-bell-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (EntitySubscription $record): void {
                        $record->delete();

                        Notification::make()
                            ->title('Unsubscribed successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No subscriptions')
            ->emptyStateDescription('Subscribe to workitems to receive notifications when they change.');
    }
}
