<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationBuilder;
use Illuminate\Support\Facades\Auth;

class Navigation
{
    public static function main(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $items = [
            NavigationItem::make('Dashboard')
                ->icon('heroicon-o-home')
                ->url(route('filament.admin.pages.dashboard'))
                ->isActiveWhen(fn(): bool => request()->reouteIs('filament.admin.pages.dashboard')),
        ];

        // Menu Konten
        $contentItems = [];

        // Artikel - tersedia untuk semua role
        $contentItems[] = NavigationItem::make('Artikel')
            ->icon('heroicon-o-newspaper')
            ->url(ArticleResource::getUrl())
            ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.articles.*'));

        // Konten - hanya admin dan editor
        if (in_array($user->role, ['admin', 'editor'])) {
            // Kategori
            $contentItems[] = NavigationItem::make('Kategori')
                ->icon('heroicon-o-tag')
                ->url(CategoryResource::getUrl())
                ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.categories.*'));

            // Tag
            $contentItems[] = NavigationItem::make('Tag')
                ->icon('heroicon-o-hashtag')
                ->url(TagResource::getUrl())
                ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.tags.*'));

            // Komentar
            $contentItems[] = NavigationItem::make('Komentar')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->url(CommentResource::getUrl())
                ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.comments.*'));

            // Media
            $contentItems[] = NavigationItem::make('Media')
                ->icon('heroicon-o-photo')
                ->url(MediaResource::getUrl())
                ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.media.*'));
        }

        // Menu Pengaturan
        $settingItems = [];

        // Newslettter - admin dan editor
        if (in_array($user->role, ['admin', 'editor'])) {
            $settingItems[] = NavigationItem::make('Subsciber Newsletter')
                ->icon('heroicon-o-envelope')
                ->url(NewsletterSubscriberResource::getUrl())
                ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.newsletter-subscribers.*'));
        }

        // User Management - hanya admin
        if ($user->role === 'admin') {
            $settingItems[] = NavigationItem::make('Pengguna')
                ->icon('heroicon-o-users')
                ->url(UserResource::getUrl())
                ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.users.*'));
        }

        // Gabungkan semua menu
        $groups = [
            NavigationGroup::make('Konten')
                ->items($contentItems)
                ->collapsed(false),

            NavigationGroup::make('Pengaturan')
                ->items($settingItems)
                ->collapsed(false),
        ];

        return $groups;
    }
}
