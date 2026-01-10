<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\PedidoItem;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class RelatorioProdutos extends BaseWidget
{
    // CORREÇÃO: Adicionado 'static' (obrigatório em TableWidgets do Filament v4)
    protected static ?string $heading = 'Engenharia de Cardápio (Top Vendas)';
    
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                // 1. Criamos a Subquery com o SQL Bruto e Agrupado
                $subquery = DB::table('pedido_items')
                    ->join('pedidos', 'pedido_items.pedido_id', '=', 'pedidos.id')
                    ->where('pedidos.status', '!=', 'cancelado')
                    // Truque: Geramos um ID fake para o Filament não se perder na listagem
                    ->selectRaw('MAX(pedido_items.id) as id') 
                    ->selectRaw('pedido_items.nome_produto')
                    ->selectRaw('pedido_items.categoria')
                    ->selectRaw('SUM(pedido_items.quantidade) as total_qtd')
                    ->selectRaw('SUM(pedido_items.preco * pedido_items.quantidade) as total_valor')
                    ->groupBy('pedido_items.nome_produto', 'pedido_items.categoria');

                // 2. Envolvemos numa query Eloquent usando 'fromSub'
                return PedidoItem::query()
                    ->fromSub($subquery, 'pedido_items')
                    ->select('*');
            })
            ->defaultSort('total_valor', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                
                TextColumn::make('nome_produto')
                    ->label('Produto')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('categoria')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Bebidas' => 'info',
                        'Lanches' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('total_qtd')
                    ->label('Qtd.')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_valor')
                    ->label('Faturamento')
                    ->money('EUR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->paginated([5, 10, 25]);
    }
}