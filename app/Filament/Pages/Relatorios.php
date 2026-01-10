<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use App\Filament\Widgets\RelatorioHorarios;
use App\Filament\Widgets\RelatorioProdutos;

class Relatorios extends Page
{
    // 1. Configurações de Navegação
    // FIX: O tipo deve ser exato: string | BackedEnum | null
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Relatórios';

    protected static ?string $title = 'Relatórios Gerenciais';

    // 2. Configuração da View (Mantemos SEM static, conforme correção anterior)
    protected string $view = 'filament.pages.relatorios';

    // 3. Widgets
    protected function getHeaderWidgets(): array
    {
        return [
            RelatorioHorarios::class,
            RelatorioProdutos::class,
        ];
    }
}