<?php

namespace App\Filament\Pages;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Models\NewsletterSubscriber;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-dashboard';

    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidget(): array
    {
        return [
            StatsOverviewWidget::make(
                [
                    Stat::make('Total Articles', Article::count())
                        ->description('32% increase')
                        ->descriptionIcon('heroicon-m-arrow-trending-up')
                        ->color('success'),
                    Stat::make('Total Users', User::count())
                        ->description('15% increase')
                        ->descriptionIcon('heroicon-m-arrow-trending-up')
                        ->color('primary'),
                    Stat::make('Pending Comments', Comment::where('status', 'pending')->count())
                        ->description('5% decrease')
                        ->descriptionIcon('heroicon-m-arrow-trending-up')
                        ->color('warning'),
                    Stat::make('Newsletter Subscribers', NewsletterSubscriber::where('status', 'active')->count())
                        ->description('10% increase')
                        ->descriptionIcon('heroicon-m-arrow-trending-up')
                        ->color('info'),
                ]
            ),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\LatestArticlesWidget::class,
            \App\Filament\Widgets\LatestCommentsWidget::class,
        ];
    }
}
