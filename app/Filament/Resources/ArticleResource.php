<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Filament\Resources\ArticleResource\RelationManagers;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextArea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    public static function form(Form $form): Form
    {
        $user = Auth::user();

        return $form
            ->schema([
                //
                Section::make('Konten Artikel')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Artikel')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('Masukkan judul artikel')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                            ->label('Slug Artikel')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('Masukkan slug artikel')
                            ->unique(Article::class, 'slug', ignoreRecord: true)
                            ->disabled(),

                        Select::make('user_id')
                            ->label('Penulis')
                            ->relationship('user', 'name')
                            ->default($user->id)
                            ->disabled(fn(): bool => $user->role !== 'admin')
                            ->required(),

                        RichEditor::make('content')
                            ->label('Konten Artikel')
                            ->required()
                            ->placeholder('Masukkan konten artikel')
                            ->columnSpanFull(),

                        TextArea::make('excerpt')
                            ->label('Ringkasan Artikel')
                            ->required()
                            ->maxLength(50000)
                            ->placeholder('Masukkan ringkasan artikel')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Media')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('featured_image')
                            ->collection('featured_images')
                            ->responsiveImages() // Aktifkan gambar responsif
                            ->imageEditor() // Aktifkan editor gambar
                            ->downloadable() // Izinkan download
                            ->openable() // Izinkan preview
                            ->label('Gambar Utama')
                            ->helperText('Ukuran disarankan: 1200x630 piksel')
                            ->required(),

                        TextInput::make('featured_image_alt')
                            ->label('Teks Alternatif Gambar')
                            ->maxLength(255)
                            ->required(),
                    ])->columns(2),

                Section::make('Kategori & Tag')
                    ->schema([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->preload()
                            ->searchable()
                            ->required(),

                        Select::make('tags')
                            ->label('Tag')
                            ->multiple()
                            ->relationship('tags', 'name')
                            ->preload()
                            ->searchable()
                            ->options(Tag::all()->pluck('name', 'id'))
                            ->required(),
                    ])->columns(2),

                Section::make('Pengaturan')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Dipublikasikan',
                                'scheduled' => 'Dijadwalkan',
                                'archived' => 'Diarsipkan',
                            ])
                            ->default('draft')
                            ->required(),

                        DateTimePicker::make('published_at')
                            ->label('Tanggal Publikasi')
                            ->required()
                            ->native(false),

                        Toggle::make('is_featured')
                            ->label('Tampilkan di Halaman Utama')
                            ->default(false)
                            ->inline(),

                        Toggle::make('is_breaking')
                            ->label('Berita Terkini')
                            ->default(false)
                            ->inline(),

                        TextInput::make('reading_time')
                            ->label('Waktu Membaca (menit)')
                            ->numeric()
                            ->suffix('menit'),
                    ])->columns(2),

                Section::make('SEO')
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('Judul Meta')
                            ->maxLength(255)
                            ->placeholder('Masukkan judul meta artikel'),

                        TextInput::make('meta_description')
                            ->label('Deskripsi Meta')
                            ->maxLength(500)
                            ->placeholder('Masukkan deskripsi meta artikel'),

                        TextInput::make('meta_keywords')
                            ->label('Kata Kunci Meta')
                            ->maxLength(500)
                            ->placeholder('Masukkan kata kunci meta artikel'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->modifyQueryUsing(fn(Builder $query) => $user->role === 'author'
                ? $query->where('user_id', $user->id)
                : $query)
            ->columns([
                //
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Gambar Utama')
                    ->collection('featured_images')
                    ->square(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Artikel')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'scheduled' => 'info',
                        'archived' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('is_breaking')
                    ->label('Breaking')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Tanggal Publikasi')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Dilihat')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Dipublikasikan',
                        'scheduled' => 'Dijadwalkan',
                        'archived' => 'Diarsipkan',
                    ]),

                Tables\Filters\SelectFilter::make('is_featured')
                    ->query(fn(Builder $query, $value) => $query->where('is_featured', $value))
                    ->label('Featured Only'),

                Tables\Filters\SelectFilter::make('is_breaking')
                    ->query(fn(Builder $query, $value) => $query->where('is_breaking', $value))
                    ->label('Breaking News Only'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->hidden(fn(Article $record): bool => !Gate::allows('view', $record)),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->hidden(fn(Article $record): bool => !Gate::allows('update', $record)),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->hidden(fn(Article $record): bool => !Gate::allows('delete', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn(): bool => !Gate::allows('deleteAny', Article::class)),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->hidden(fn(): bool => !Gate::allows('forceDelete', Article::class)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->hidden(fn(): bool => !Gate::allows('restore', Article::class)),
                ])
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Artikel')
                    ->hidden(fn(): bool => !Gate::allows('create', Article::class)),
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
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'view' => Pages\ViewArticle::route('/{record}'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
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
