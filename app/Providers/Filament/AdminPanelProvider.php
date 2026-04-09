<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\AdminTrendChart;
use App\Filament\Widgets\TopMenusChart;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $adminPath = trim((string) env('ADMIN_PATH', 'secure-panel-9x7k2'), '/');

        return $panel
            ->default()
            ->id('admin')
            ->path($adminPath)
            ->login()
            ->brandName('Japemethe')
            ->brandLogo(asset('storage/logo.webp'))
            ->favicon(asset('storage/logo.webp'))
            ->brandLogoHeight('3rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Operasional'),
                NavigationGroup::make('Menu'),
                NavigationGroup::make('Transaksi'),
                NavigationGroup::make('Marketing'),
                NavigationGroup::make('Laporan'),
            ])
            ->navigationItems([
                NavigationItem::make('Kasir')
                    ->group('Operasional')
                    ->icon('heroicon-o-computer-desktop')
                    ->sort(1)
                    ->url(fn (): string => url('/' . $adminPath . '/kasir-app'))
                    ->visible(fn (): bool => in_array(Auth::user()?->role, ['admin', 'kasir'], true)),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StatsOverview::class,
                AdminTrendChart::class,
                TopMenusChart::class,
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
