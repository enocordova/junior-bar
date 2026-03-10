<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Configuracao;
use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    // CORREÇÃO: Removemos a palavra 'static' aqui.
    // Agora é compatível com a classe pai do Filament.
    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // 1. Calcular Faturação de Hoje
        $faturacaoHoje = PedidoItem::whereHas('pedido', function ($query) {
            $query->where('status', 'pago')
                  ->whereDate('created_at', today());
        })->sum(DB::raw('preco * quantidade'));

        // 2. Pedidos na Fila (Pendente ou Preparo)
        $pedidosFila = Pedido::whereIn('status', ['pendente', 'preparo'])->count();

        // 3. Mesas Ativas
        $mesasAtivas = Pedido::whereIn('status', ['pendente', 'preparo', 'pronto'])
            ->distinct('mesa')
            ->count();

        return [
            Stat::make('Caixa do Dia', Configuracao::countryPreset()['currency_symbol'] . ' ' . number_format($faturacaoHoje, 2, ',', '.'))
                ->description('Vendas finalizadas hoje')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                // Removemos o chart estático para ficar mais limpo, ou pode manter se quiser dados fake
                ->chart([7, 3, 10, 5, 15, 4, 17]),

            Stat::make('Cozinha / Bar', $pedidosFila)
                ->description('Pedidos em andamento')
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning'),

            Stat::make('Mesas Ocupadas', $mesasAtivas)
                ->description('Clientes atendidos agora')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}