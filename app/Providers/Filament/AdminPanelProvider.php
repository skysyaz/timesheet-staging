<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\RecordSiteTraffic;
use App\Support\LocalAvatarProvider;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->profile()
            ->defaultAvatarProvider(LocalAvatarProvider::class)
            ->multiFactorAuthentication(
                providers: [
                    AppAuthentication::make()
                        ->recoverable(),
                ],
                isRequired: fn (): bool => config('security.mfa_required_for_admin')
                    && ! app()->environment('testing')
                    && auth()->check()
                    && auth()->user()->isAdmin(),
            )
            ->brandName('')
            ->brandLogo(asset('logo.webp'))
            ->brandLogoHeight('2rem')
            ->favicon(null)
            ->font('Inter')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->defaultThemeMode(ThemeMode::Light)
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->spaUrlExceptions([
                '/pdf/*',
                '/weekly-hours/*',
                '/admin/projects*',
            ])
            ->colors([
                'primary' => Color::hex('#1B3860'),
                'gray' => Color::Slate,
                'danger' => Color::hex('#BE123C'),
                'warning' => Color::hex('#B45309'),
                'success' => Color::hex('#047857'),
                'info' => Color::hex('#1D4ED8'),
            ])
            ->navigationGroups([
                NavigationGroup::make('Overview'),
                NavigationGroup::make('Time Tracking'),
                NavigationGroup::make('Reports'),
                NavigationGroup::make('Management'),
                NavigationGroup::make('Administration'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RecordSiteTraffic::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn () => view('filament.hooks.favicons'),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn () => view('filament.hooks.ui-interactivity'),
            );
    }
}
