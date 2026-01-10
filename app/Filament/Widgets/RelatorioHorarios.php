<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

class RelatorioHorarios extends ChartWidget
{
    // GRÁFICO: O heading NÃO PODE SER static
    protected ?string $heading = 'Picos de Atendimento (Por Hora)';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // ... (o seu código de busca de dados permanece igual)
        $dados = Pedido::select(DB::raw('HOUR(created_at) as hora'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', now()->subDays(30))
            ->where('status', '!=', 'cancelado')
            ->groupBy('hora')
            ->orderBy('hora')
            ->get()
            ->pluck('total', 'hora')
            ->toArray();

        $data = [];
        $labels = [];
        
        for ($i = 0; $i < 24; $i++) {
            $labels[] = sprintf('%02d:00', $i);
            $data[] = $dados[$i] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Volume de Pedidos (30 dias)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(239, 163, 36, 0.2)',
                    'borderColor' => '#efa324',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}