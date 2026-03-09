<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProdutoController;

/*
|--------------------------------------------------------------------------
| Rotas API protegidas por autenticação + role
|--------------------------------------------------------------------------
| Sem throttle agressivo — sistema interno KDS com polling frequente
| e múltiplas abas abertas (garçom + cozinha + gerente simultâneos).
*/

// Health check público (sem auth) — usado pelo frontend para verificar se o backend responde
Route::get('/health', fn () => response()->json(['status' => 'ok', 'ts' => now()->timestamp]));

Route::middleware(['web', 'auth'])->group(function () {

    // GARÇOM + ADMIN: Criar pedidos, listar cardápio e estado das mesas
    Route::middleware(['role:garcom,admin'])->group(function () {
        Route::post('/criar-pedido', [PedidoController::class, 'salvarPedido']);
        Route::get('/produtos', [ProdutoController::class, 'index']);
        Route::get('/mesas-configuradas', [PedidoController::class, 'listarMesasCadastradas']);
        Route::get('/gerente/resumo-mesas', [PedidoController::class, 'resumoMesas']);
    });

    // COZINHA + ADMIN: Ver pedidos ativos e atualizar status
    Route::middleware(['role:cozinha,admin'])->group(function () {
        Route::get('/pedidos-ativos', [PedidoController::class, 'listarAtivos']);
        Route::post('/atualizar-status/{id}', [PedidoController::class, 'atualizarStatus']);
        Route::post('/cancelar-pedido/{id}', [PedidoController::class, 'cancelar']);
    });

    // GERENTE (ADMIN): Operações de gestão (escrita)
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/gerente/fechar-mesa/{mesa}', [PedidoController::class, 'fecharMesa']);
        Route::delete('/gerente/remover-item/{id}', [PedidoController::class, 'removerItem']);
        Route::post('/gerente/atualizar-qtd-item/{id}', [PedidoController::class, 'atualizarQuantidadeItem']);
        Route::post('/gerente/adicionar-item-mesa', [PedidoController::class, 'adicionarItemMesa']);
    });

});
