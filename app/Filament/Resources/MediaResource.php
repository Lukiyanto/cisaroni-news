<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages;
use App\Filament\Resources\MediaResource\RelationManagers;
use App\Models\Media;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $modelLabel = 'Media';

    protected static ?string $pluralModelLabel = 'Media';

    protected static ?string $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 6;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\Section::make('File Media')
                    ->schema([
                        Forms\Components\FileUpload::make('path')
                            ->label('Unggah File Media')
                            ->required()
                            ->directory('media')
                            ->preserveFilenames()
                            ->openable()
                            ->downloadable(),
                    ]),

                Forms\Components\Section::make('Informasi Media')
                    ->schema([
                        Forms\Components\TextInput::make('original_name')
                            ->label('Nama Asli')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama asli file media'),
                        Forms\Components\TextInput::make('mime_type')
                            ->label('Tipe MIME')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan tipe MIME file media'),
                        Forms\Components\TextInput::make('size')
                            ->label('Ukuran (bytes)')
                            ->required()
                            ->numeric()
                            ->placeholder('Masukkan ukuran file media dalam bytes'),
                        Forms\Components\TextInput::make('alt_text')
                            ->label('Teks Alt')
                            ->maxLength(500)
                            ->placeholder('Masukkan teks alternatif untuk media'),
                        Forms\Components\TextInput::make('caption')
                            ->label('Keterangan')
                            ->maxLength(1000)
                            ->placeholder('Masukkan keterangan untuk media'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\ImageColumn::make('path')
                    ->label('Media')
                    ->square(),
                Tables\Columns\TextColumn::make('original_name')
                    ->label('Nama Asli')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Tipe MIME')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('size')
                    ->label('Ukuran (bytes)')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => formatBytes($state)),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengunggah')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
                Tables\Filters\SelectFilter::make('mime_type')
                    ->options(fn () => Media::query()->pluck('mime_type', 'mime_type')->unique())
                    ->label('Tipe MIME'),
                Tables\Filters\Filter::make('images')
                    ->query(fn (Builder $query): Builder => $query->where('mime_type', 'like', 'image/%'))
                    ->label('Hanya Gambar'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'view' => Pages\ViewMedia::route('/{record}'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}

// Helper function to format bytes
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}