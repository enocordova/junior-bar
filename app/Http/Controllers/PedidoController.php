<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // <--- IMPORTANTE
use App\Models\Pedido;

class PedidoController extends Controller
{
    public function salvarPedido(Request $request)
    {
        $validated = $request->validate([
            'mesa' => 'required|integer',
            'itens' => 'required|array|min:1',
            'itens.*.nome' => 'required|string',
            'itens.*.qtd' => 'required|integer|min:1',
            'itens.*.obs' => 'nullable|string|max:100',
        ]);

        try {
            $pedido = DB::transaction(function () use ($validated) {
                $pedido = Pedido::create([
                    'mesa' => $validated['mesa'],
                    'status' => 'pendente'
                ]);

                $itensFormatados = collect($validated['itens'])->map(function($item) {
                    return [
                        'nome_produto' => $item['nome'],
                        'quantidade' => $item['qtd'],
                        'observacao' => $item['obs'] ?? null
                    ];
                });

                $pedido->itens()->createMany($itensFormatados);

                return $pedido->load('itens');
            });

            // --- NOTIFICAÇÃO REAL-TIME (Dispara e Esquece) ---
            try {
                Http::timeout(1)->post('http://127.0.0.1:3000/api/broadcast', [
                    'evento' => 'cozinha_novo_pedido',
                    'dados' => $pedido
                ]);
            } catch (\Exception $e) {
                Log::error("Erro ao notificar Node.js: " . $e->getMessage());
                // Não falha o pedido se o socket estiver offline, apenas loga.
            }

            return response()->json(['status' => 'sucesso', 'pedido' => $pedido], 201);

        } catch (\Exception $e) {
            Log::error('Erro Crítico: ' . $e->getMessage());
            return response()->json(['status' => 'erro', 'msg' => 'Erro interno.'], 500);
        }
    }

    public function listarAtivos()
    {
        return Pedido::with('itens')
            ->whereIn('status', ['pendente', 'preparo'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function atualizarStatus(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update(['status' => $request->status]);
        
        // Notifica atualização de status
        try {
            Http::timeout(1)->post('http://127.0.0.1:3000/api/broadcast', [
                'evento' => $request->status === 'pronto' ? 'pedido_pronto' : 'cozinha_atualizar_status',
                'dados' => ['id' => $pedido->id, 'status' => $request->status, 'mesa' => $pedido->mesa]
            ]);
        } catch (\Exception $e) {
            Log::error("Erro Socket Status: " . $e->getMessage());
        }

        return response()->json(['status' => 'sucesso', 'pedido' => $pedido]);
    }
}