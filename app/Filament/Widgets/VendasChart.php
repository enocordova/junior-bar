<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\PedidoItem;
use Illuminate\Support\Facades\DB;

class VendasChart extends ChartWidget
{
    // [CORRIGIDO] Removido 'static'
    protected ?string $heading = 'Evolução das Vendas (7 Dias)';
    
    // Este mantém-se static (padrão do Filament para ordenação)
    protected static ?int $sort = 2; 
    
    // Largura total
    protected int | string | array $columnSpan = 'full';
    
    // [CORRIGIDO] Removido 'static'
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Loop: De 6 dias atrás até hoje (7 dias no total)
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $labels[] = $date->format('d/m');

            $totalDia = PedidoItem::whereHas('pedido', function ($query) use ($date) {
                $query->where('status', 'pago')
                      ->whereDate('created_at', $date->format('Y-m-d'));
            })->sum(DB::raw('preco * quantidade'));

            $data[] = $totalDia;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Faturação (€)',
                    'data' => $data,
                    'borderColor' => '#7ed957',
                    'backgroundColor' => 'rgba(126, 217, 87, 0.1)',
                    'pointBackgroundColor' => '#7ed957',
                    'pointBorderColor' => '#7ed957',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}