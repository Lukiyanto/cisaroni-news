<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LatestCommentsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(Comment::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('artile.title')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('author_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('content')
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'spam' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])

            ->actions([
                Tables\Actions\Action::make('approve')
                    ->action(fn(Comment $record) => $record->update(['status' => 'approved']))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(Comment $record) => $record->status !== 'approved'),
                Tables\Actions\Action::make('reject')
                    ->action(fn(Comment $record) => $record->update(['status' => 'rejected']))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(Comment $record) => $record->status !== 'rejected'),
            ]);
    }
}
