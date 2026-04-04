<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Support\Assets\Css;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ->theme('css/filament/admin/theme.css')
            // ->viteTheme('resources/css/filament/admin/theme.css')
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('NEX-COMM')
            ->profile(\App\Filament\Pages\Auth\EditProfile::class)
            // ✅ FIXED: Assets now use the correct class-based registration
            ->assets([
                Css::make('custom-stylesheet', asset('css/custom-filament.css')),
            ])
            ->resources([
                \App\Filament\Resources\UserResource::class,
            ])
            ->colors([
                'primary' => Color::Blue, 
                'gray' => Color::Slate,
            ])
            // ->login(\App\Filament\Pages\Auth\Login::class)
            ->login(null)
            ->authGuard('web')
            ->darkMode(false)
            ->font('Inter')
            
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([]) 
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
            ])
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label(fn () => auth()->user()->name)
            ])
            ->renderHook(
                'panels::user-menu.before',
                fn (): HtmlString => new HtmlString('
                    <div class="hidden md:flex flex-col items-end justify-center mr-3">
                        <span class="text-sm font-bold text-gray-700 leading-none">' . (auth()->user() ? auth()->user()->name : '') . '</span>
                        <span class="text-[10px] font-medium text-gray-400 uppercase tracking-widest mt-1"></span>
                    </div>
                ')
            )
            ->renderHook(
                'panels::head.done',
                fn (): HtmlString => new HtmlString('
                    <style>
                        /* 1. SIDEBAR - Deep Slate Grey Background */
                        .fi-sidebar, 
                        .fi-sidebar nav, 
                        aside.fi-sidebar { 
                            background-color: #1e293b !important; 
                        }

                        /* 2. SIDEBAR TEXT & ICONS */
                        .fi-sidebar-item-button, 
                        .fi-sidebar-item-label,
                        .fi-sidebar-item-icon,
                        .fi-sidebar-header > div,
                        .fi-sidebar-header h2 {
                            color: #f8fafc !important;
                        }

                        /* 3. ACTIVE STATE */
                        .fi-sidebar-item-active,
                        .fi-sidebar-item-active .fi-sidebar-item-button {
                            background-color: #2563eb !important; 
                            color: #ffffff !important;
                        }

                        .fi-sidebar-item-button:hover {
                            background-color: rgba(255, 255, 255, 0.1) !important;
                        }

                        /* 4. GROUP LABELS */
                        .fi-sidebar-group-label {
                            color: #94a3b8 !important;
                            font-weight: bold;
                        }

                        /* 5. MAIN CONTENT AREA */
                        .fi-main {
                            background-color: #f1f5f9 !important;
                        }

                        /* 6. TOPBAR (Header) */
                        .fi-topbar {
                            background-color: #ffffff !important;
                            border-bottom: 1px solid #e2e8f0 !important;
                        }

                        /* 7. WIDGETS */
                        .fi-wi-stats-overview-stat, .fi-wi-widget {
                            background-color: #ffffff !important;
                            border: 1px solid #e2e8f0 !important;
                            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                            border-radius: 12px !important;
                        }
                    </style>
            
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <script src="https://media-library.cloudinary.com/global/all.js" type="text/javascript"></script>
                ')
            );
    }
}