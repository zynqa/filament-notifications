<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Filament\Resources\AdminNotificationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Recipients';

    protected static ?string $icon = 'heroicon-o-users';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('pivot.read_at')
                    ->label('Read Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($record): string => $record->pivot->read_at ? 'Read' : 'Unread'),

                Tables\Columns\TextColumn::make('pivot.read_at')
                    ->label('Read At')
                    ->dateTime()
                    ->placeholder('Not read yet'),

                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Received At')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('read_at')
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
            ->actions([
                // No edit/delete actions for individual recipients
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->emptyStateHeading('No Recipients')
            ->emptyStateDescription('This notification has no recipients assigned.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
