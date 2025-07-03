<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kategori')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama kategori')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug Kategori')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan slug kategori')
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('parent_id')
                            ->label('Kategori Induk')
                            ->options(fn() => Category::whereNull('parent_id')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi Kategori')
                            ->maxLength(1000)
                            ->placeholder('Masukkan deskripsi kategori')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('Tampilan')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Gambar Kategori')
                            ->image()
                            ->maxSize(1024)
                            ->directory('categories')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                            ->placeholder('Unggah gambar kategori'),

                        Forms\Components\ColorPicker::make('color')
                            ->default('#ffffff')
                            ->label('Warna Kategori'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Urutan Kategori')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Judul Meta')
                            ->maxLength(255)
                            ->placeholder('Masukkan judul meta untuk SEO'),

                        Forms\Components\Textarea::make('meta_description')
                            ->label('Deskripsi Meta')
                            ->maxLength(500)
                            ->placeholder('Masukkan deskripsi meta untuk SEO')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Warna'),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Kategori Utama'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('posts_count')
                    ->label('Jumlah Post')
                    ->counts('posts')
                    ->alignCenter()
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Kategori Parent')
                    ->relationship('parent', 'name')
                    ->placeholder('Semua Parent'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->placeholder('Semua Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->hidden(fn(Category $record): bool => !Gate::allows('view', $record)),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->hidden(fn(Category $record): bool => !Gate::allows('update', $record)),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->hidden(fn(Category $record): bool => !Gate::allows('delete', $record)),
                Tables\Actions\RestoreAction::make()
                    ->label('Pulihkan')
                    ->hidden(fn(Category $record): bool => !Gate::allows('restore', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus')
                        ->hidden(fn(): bool => !Gate::allows('delete', Category::class)),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen')
                        ->hidden(fn(): bool => !Gate::allows('forceDelete', Category::class)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Pulihkan')
                        ->hidden(fn(): bool => !Gate::allows('restore', Category::class)),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Kategori')
                    ->hidden(fn(): bool => !Gate::allows('create', Category::class)),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 10 ? 'warning' : 'primary';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'editor']);
    }
}
