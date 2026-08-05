<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Recipients';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-users';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('pivot.read_at')
                    ->label('Read Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($record): string => $record->pivot->read_at ? 'Read' : 'Unread'),

                TextColumn::make('pivot.read_at')
                    ->label('Read At')
                    ->dateTime()
                    ->placeholder('Not read yet'),

                TextColumn::make('pivot.created_at')
                    ->label('Received At')
                    ->dateTime(),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Read Status')
                    ->placeholder('All Recipients')
                    ->trueLabel('Read')
                    ->falseLabel('Unread')
                    ->queries(
                        true: fn (Builder $query) => $query->wherePivotNotNull('read_at'),
                        false: fn (Builder $query) => $query->wherePivotNull('read_at'),
                    ),
            ])
            ->headerActions([
                // No create action - recipients are managed from the main form
            ])
            ->recordActions([
                // No edit/delete actions for individual recipients
            ])
            ->toolbarActions([
                // No bulk actions
            ])
            ->emptyStateHeading('No Recipients')
            ->emptyStateDescription('This notification has no recipients assigned.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
