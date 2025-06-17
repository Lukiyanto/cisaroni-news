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

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $modelLabel = 'Artikel';

    protected static ?string $pluralModelLabel = 'Artikel';

    protected static ?string $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
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
                        FileUpload::make('featured_image')
                            ->image()
                            ->directory('articles')
                            ->imageEditor(),

                        TextInput::make('featured_image_alt')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Kategori & Tag')
                    ->schema([
                        Select::make('categori_id')
                            ->label('Kategori')
                            ->option(Category::all()->pluck('name', 'id'))
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
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Artikel')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('categori.name')
                    ->label('Kategori')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'green',
                        'scheduled' => 'blue',
                        'archived' => 'red',
                    }),
                Tables\Columns\TextColumn::make('is_featured')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_breaking')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Tanggal Publikasi')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('views_count')
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
