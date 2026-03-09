<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem; // Importante
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            //->login()
            ->colors([
                'primary' => '#7ed957',
            ])
            // --- ITENS DE NAVEGAÇÃO PERSONALIZADOS ---
            ->navigationItems([
                // 1. Painel Gerente
                NavigationItem::make('Gerente')
                    ->url('/gerente', shouldOpenInNewTab: false)
                    ->icon('heroicon-o-computer-desktop') 
                    ->group('Atendimento') 
                    ->sort(2), // Logo após Produtos (que deve ser 1)

                // 2. Painel Cozinha (KDS)
                NavigationItem::make('Cozinha')
                    ->url('/cozinha', shouldOpenInNewTab: false) // Redireciona para /cozinha
                    ->icon('heroicon-o-fire') // Ícone de fogo/cozinha
                    ->group('Atendimento')
                    ->sort(3),

                // 3. Painel Garçom
                NavigationItem::make('Garçom')
                    ->url('/garcom', shouldOpenInNewTab: false) // Redireciona para /garcom
                    ->icon('heroicon-o-user') // Ícone de usuário
                    ->group('Atendimento')
                    ->sort(4),
            ])
            // ------------------------------------------
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets padrão
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