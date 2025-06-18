<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterSubscriberResource\Pages;
use App\Filament\Resources\NewsletterSubscriberResource\RelationManagers;
use App\Models\NewsletterSubscriber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $modelLabel = 'Subscriber';

    protected static ?string $pluralModelLabel = 'Subscriber';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\Section::make('Informasi Subscriber')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->placeholder('Masukkan Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->placeholder('Masukkan Nama')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label('Pilih Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'unsubscribed' => 'Unsubscribed',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('verification_token')
                            ->label('Verifikasi Token')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('verified_at')
                            ->label('Verified At'),
                        Forms\Components\DateTimePicker::make('subcribed_at')
                            ->label('Subcribed At'),
                        Forms\Components\DateTimePicker::make('unsubcribed_at')
                            ->label('unsubcribed At'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'unsubscriber' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('subscribed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('unsubscribed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'unsubscribed' => 'Unsubscribed',
                    ]),
                Tables\Filters\Filter::make('verified')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('verified_at'))
                    ->label('Verified Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('verify')
                    ->action(fn(NewsletterSubscriber $record) => $record->verify())
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(NewsletterSubscriber $record) => !$record->verified_at),
                Tables\Actions\Action::make('unsubscribe')
                    ->action(fn(NewsletterSubscriber $record) => $record->unsubscribe())
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(NewsletterSubscriber $record) => $record->status !== 'unsubscribed'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('verify')
                        ->action(fn(NewsletterSubscriber $record) => $record->verify())
                        ->icon('heroicon-o-check')
                        ->color('success'),
                    Tables\Actions\BulkAction::make('unsubscribe')
                        ->action(fn(NewsletterSubscriber $record) => $record->unsubscribe())
                        ->icon('heroicon-o-x-mark')
                        ->color('danger'),
                    Tables\Actions\DeleteBulkAction::make(),
                ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscribers::route('/'),
            'create' => Pages\CreateNewsletterSubscriber::route('/create'),
            'view' => Pages\ViewNewsletterSubscriber::route('/{record}'),
            'edit' => Pages\EditNewsletterSubscriber::route('/{record}/edit'),
        ];
    }
}
