<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function form(Form $form): Form
    {
        $user = Auth::user();

        return $form
            ->schema([
                Section::make('Informasi Pengguna')
                    ->description('Masukkan informasi dasar pengguna')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Avatar')
                            ->placeholder('Unggah avatar')
                            ->image()
                            ->directory('avatars')
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper(),

                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->placeholder('Masukkan nama lengkap')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete('name'),

                        TextInput::make('email')
                            ->label('Email')
                            ->placeholder('Masukkan email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->autocomplete('email')
                            ->unique(User::class, 'email', ignoreRecord: true),

                        Select::make('role')
                            ->label('Peran')
                            ->placeholder('Pilih peran')
                            ->helperText('Peran ini menentukan hak akses pengguna')
                            ->options([
                                'admin' => 'Admin',
                                'editor' => 'Editor',
                                'author' => 'Author',
                            ])
                            ->disabled(fn(): bool => $user->role !== 'admin')
                            ->required()
                            ->native(false),

                        Select::make('status')
                            ->label('Status')
                            ->placeholder('Pilih status')
                            ->helperText('Status ini menentukan apakah pengguna aktif atau tidak')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                            ])
                            ->required()
                            ->native(false)
                            ->default('active'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Keamanan')
                    ->description('Pengaturan keamanan akun')
                    ->schema([
                        TextInput::make('password')
                            ->label('Kata Sandi')
                            ->placeholder('Masukkan kata sandi')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->dehydrateStateUsing(
                                fn(?string $state): ?string =>
                                filled($state) ? bcrypt($state) : null
                            )
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->autocomplete('new-password'),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Kata Sandi')
                            ->placeholder('Konfirmasi kata sandi')
                            ->password()
                            ->revealable()
                            ->same('password')
                            ->dehydrated(false)
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->autocomplete('new-password'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Profil')
                    ->description('Informasi tambahan pengguna')
                    ->schema([
                        RichEditor::make('bio')
                            ->label('Biografi')
                            ->placeholder('Tulis biografi pengguna di sini')
                            ->columnSpanFull()
                            ->maxLength(5000)
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($user) {
                if ($user?->role === 'author') {
                    return $query->where('id', $user->id);
                }

                if ($user?->role === 'editor') {
                    return $query->where('role', '!=', 'admin');
                }

                return $query;
            })
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email berhasil disalin!')
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Peran')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger',
                        'editor' => 'warning',
                        'author' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'admin' => 'heroicon-m-shield-check',
                        'editor' => 'heroicon-m-pencil-square',
                        'author' => 'heroicon-m-user',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Peran')
                    ->options([
                        'admin' => 'Admin',
                        'editor' => 'Editor',
                        'author' => 'Author',
                    ])
                    ->multiple()
                    ->indicator('Peran'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ])
                    ->default('active')
                    ->indicator('Status'),

                Tables\Filters\TrashedFilter::make()
                    ->label('Data Dihapus'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat')
                        ->hidden(fn(User $record): bool => !Gate::allows('view', $record)),
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->hidden(fn(User $record): bool => !Gate::allows('update', $record)),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->hidden(fn(User $record): bool => !Gate::allows('delete', $record)),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->hidden(fn(): bool => !Gate::allows('deleteAny', User::class)),
                ])
                    ->label('Aksi Massal'),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Pengguna Pertama'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}
