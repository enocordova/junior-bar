<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProdutoController;

Route::post('/criar-pedido', [PedidoController::class, 'salvarPedido']);
Route::get('/pedidos-ativos', [PedidoController::class, 'listarAtivos']);
Route::post('/atualizar-status/{id}', [PedidoController::class, 'atualizarStatus']);
Route::get('/produtos', [ProdutoController::class, 'index']);
Route::post('/cancelar-pedido/{id}', [PedidoController::class, 'cancelar']);
Route::get('/gerente/resumo-mesas', [PedidoController::class, 'resumoMesas']);
Route::post('/gerente/fechar-mesa/{mesa}', [PedidoController::class, 'fecharMesa']);
Route::delete('/gerente/remover-item/{id}', [PedidoController::class, 'removerItem']);
Route::post('/gerente/atualizar-qtd-item/{id}', [PedidoController::class, 'atualizarQuantidadeItem']);
Route::post('/gerente/adicionar-item-mesa', [PedidoController::class, 'adicionarItemMesa']);
Route::get('/mesas-configuradas', [PedidoController::class, 'listarMesasCadastradas']);