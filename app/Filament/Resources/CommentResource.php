<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Filament\Resources\CommentResource\RelationManagers;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\Section::make('Konten Komentar')
                    ->schema([
                        Forms\Components\Select::make('article_id')
                            ->label('Artikel')
                            ->relationship('article', 'title')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Pengguna')
                            ->relationship('user', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('parent_id')
                            ->label('Parent')
                            ->relationship('parent', 'id')
                            ->searchable(),
                        Forms\Components\TextInput::make('author_name')
                            ->label('Author')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('author_email')
                            ->label('Author Email')
                            ->reqired()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('content')
                            ->label('Content')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Moderasi')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->option([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'spam' => 'Spam',
                            ])
                            ->required()
                            ->label('Pilih Status'),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->maxLength(45),
                        Forms\Components\TextInput::make('user_agent')
                            ->label('User Agent')
                            ->maxLength(65535),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('article.title')
                    ->label('Artikel')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->searchable(),
                Tables\Columns\TextColumn::make('author_name')
                    ->label('Author')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->color(fn($state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'spam' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'spam' => 'Spam',
                    ]),
                Tables\Filters\SelectFilter::make('article')
                    ->relationship('article', 'title'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->hidden(fn(Comment $record): bool => !Gate::allows('view', $record)),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->hidden(fn(Comment $record): bool => !Gate::allows('update', $record)),
                Tables\Actions\Action::make('approve')
                    ->action(fn(Comment $record) => $record->update(['status' => 'approved']))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(Comment $record) => Gate::allows('approve', $record)),
                Tables\Actions\Action::make('reject')
                    ->action(fn(Comment $record) => $record->update(['status' => 'rejected']))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(Comment $record) => Gate::allows('reject', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn(): bool => !Gate::allows('delete', Comment::class)),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->hidden(fn(): bool => !Gate::allows('forceDelete', Comment::class)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->hidden(fn(): bool => !Gate::allows('restore', Comment::class)),
                ]),
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
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'view' => Pages\ViewComment::route('/{record}'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
