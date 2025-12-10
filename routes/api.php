<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProdutoController;

Route::post('/criar-pedido', [PedidoController::class, 'salvarPedido']);
Route::get('/pedidos-ativos', [PedidoController::class, 'listarAtivos']);
Route::post('/atualizar-status/{id}', [PedidoController::class, 'atualizarStatus']);
Route::get('/produtos', [ProdutoController::class, 'index']);