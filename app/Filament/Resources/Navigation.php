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

        // Menu Pengaturan
        $settingItems = [];

        // User Management - hanya admin
        if ($user->role === 'admin') {
            $settingItems[] = NavigationItem::make('Pengguna')
                ->icon('heroicon-o-users')
                ->url(UserResource::getUrl())
                ->isActiveWhen(fn(): bool => request()->routeIs('filament.admin.resources.users.*'));
        }

        // Gabungkan menu
        $groups = [
            NavigationGroup::make('Pengaturan')
                ->items($settingItems)
                ->collapsed(false),
        ];

        return $groups;
    }
}
