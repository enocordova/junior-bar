<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\PedidoItem;

class PedidoController extends Controller
{
    /**
     * =========================================================================
     * GARÇOM: CRIAÇÃO COM FUSÃO INTELIGENTE DE ADICIONAIS
     * =========================================================================
     */
    public function salvarPedido(Request $request)
    {
        $validated = $request->validate([
            'mesa' => 'required|integer',
            'itens' => 'required|array|min:1',
            'itens.*.id_produto' => 'required|exists:produtos,id',
            'itens.*.qtd' => 'required|integer|min:1',
            'itens.*.obs' => 'nullable|string|max:100',
        ]);

        try {
            $ids = collect($validated['itens'])->pluck('id_produto');
            $produtosDb = Produto::whereIn('id', $ids)->get()->keyBy('id');

            $pedido = DB::transaction(function () use ($validated, $produtosDb) {
                
                // 1. Busca ou Cria Pedido
                $pedido = Pedido::where('mesa', $validated['mesa'])
                    ->whereIn('status', ['pendente', 'preparo'])
                    ->latest()
                    ->first();

                $isNovoPedido = false;

                if (!$pedido) {
                    $pedido = Pedido::create([
                        'mesa'    => $validated['mesa'],
                        'status'  => 'pendente',
                        'user_id' => Auth::id(),
                    ]);
                    $isNovoPedido = true;
                }

                // 2. Processamento dos Itens
                foreach ($validated['itens'] as $item) {
                    $produtoReal = $produtosDb[$item['id_produto']];
                    $obsItem = $item['obs'] ?? null;

                    // --- CENÁRIO: RASCUNHO (Pendente) ---
                    // Funde tudo silenciosamente.
                    if ($pedido->status === 'pendente') {
                        $query = $pedido->itens()->where('nome_produto', $produtoReal->nome);
                        
                        if (is_null($obsItem)) $query->whereNull('observacao');
                        else $query->where('observacao', $obsItem);

                        $itemExistente = $query->first();

                        if ($itemExistente) {
                            $itemExistente->quantidade += $item['qtd'];
                            $itemExistente->save();
                        } else {
                            $pedido->itens()->create([
                                'nome_produto' => $produtoReal->nome,
                                'quantidade' => $item['qtd'],
                                'observacao' => $obsItem,
                                'preco' => $produtoReal->preco,
                                'categoria' => $produtoReal->categoria
                            ]);
                        }
                    }
                    else {
                        // --- CENÁRIO: EM PREPARO (Smart Merge) ---
                        // Procura se JÁ EXISTE uma linha de acréscimo "viva"
                        $linhaAdicional = $pedido->itens()
                            ->where('nome_produto', $produtoReal->nome)
                            ->where('observacao', 'like', 'Acrescentar +%') // A chave da fusão
                            ->latest()
                            ->first();

                        if ($linhaAdicional) {
                            // FUSÃO: Soma o novo pedido ao que já era acréscimo
                            $novaQtdTotal = $linhaAdicional->quantidade + $item['qtd'];
                            
                            $linhaAdicional->quantidade = $novaQtdTotal;
                            $linhaAdicional->observacao = "Acrescentar + {$novaQtdTotal}"; 
                            $linhaAdicional->save();
                        } else {
                            // Primeira vez que adiciona extra? Cria linha nova.
                            $obsFinal = "Acrescentar + {$item['qtd']}";
                            if ($obsItem) $obsFinal .= " [{$obsItem}]";

                            $pedido->itens()->create([
                                'nome_produto' => $produtoReal->nome,
                                'quantidade' => $item['qtd'],
                                'observacao' => $obsFinal,
                                'preco' => $produtoReal->preco,
                                'categoria' => $produtoReal->categoria
                            ]);
                        }
                    }
                }

                $pedido->load('itens');
                $pedido->event_type = $isNovoPedido ? 'create' : 'update';

                return $pedido;
            });

            $this->broadcastToNode('cozinha_novo_pedido', $pedido);

            return response()->json(['status' => 'sucesso', 'pedido' => $pedido], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar pedido: ' . $e->getMessage());
            return response()->json(['status' => 'erro'], 500);
        }
    }

    /**
     * =========================================================================
     * GERENTE: ADIÇÃO AVULSA COM CÁLCULO
     * =========================================================================
     */
    public function adicionarItemMesa(Request $request)
    {
        $request->validate([
            'mesa' => 'required|integer',
            'produto_id' => 'required|exists:produtos,id',
            'quantidade' => 'sometimes|integer|min:1',
            'observacao' => 'sometimes|nullable|string|max:255',
        ]);

        try {
            $produto = Produto::findOrFail($request->produto_id);
            $qtd = (int) $request->input('quantidade', 1);
            $obsUsuario = $request->input('observacao');

            $pedido = Pedido::where('mesa', $request->mesa)
                ->whereIn('status', ['pendente', 'preparo'])
                ->latest()->first();

            $isNovo = false;
            if (!$pedido) {
                $pedido = Pedido::create(['mesa' => $request->mesa, 'status' => 'pendente', 'user_id' => Auth::id()]);
                $isNovo = true;
            }

            if ($pedido->status === 'preparo') {
                // Pedido já em preparo — marcar como "Acrescentar"
                $obsText = "Acrescentar + {$qtd}";
                if ($obsUsuario) $obsText .= " [{$obsUsuario}]";

                $linhaExistente = !$obsUsuario
                    ? $pedido->itens()->where('nome_produto', $produto->nome)->where('observacao', 'like', 'Acrescentar +%')->whereRaw("observacao NOT LIKE '%[%'")->first()
                    : null;

                if ($linhaExistente) {
                    $linhaExistente->quantidade += $qtd;
                    $linhaExistente->observacao = "Acrescentar + {$linhaExistente->quantidade}";
                    $linhaExistente->save();
                } else {
                    $pedido->itens()->create([
                        'nome_produto' => $produto->nome,
                        'quantidade' => $qtd,
                        'preco' => $produto->preco,
                        'categoria' => $produto->categoria,
                        'observacao' => $obsText,
                    ]);
                }
            } else {
                // Pedido pendente — agrupar se sem observação
                if (!$obsUsuario) {
                    $existente = $pedido->itens()->where('nome_produto', $produto->nome)->whereNull('observacao')->first();
                    if ($existente) {
                        $existente->quantidade += $qtd;
                        $existente->save();
                    } else {
                        $pedido->itens()->create([
                            'nome_produto' => $produto->nome,
                            'quantidade' => $qtd,
                            'preco' => $produto->preco,
                            'categoria' => $produto->categoria,
                        ]);
                    }
                } else {
                    $pedido->itens()->create([
                        'nome_produto' => $produto->nome,
                        'quantidade' => $qtd,
                        'preco' => $produto->preco,
                        'categoria' => $produto->categoria,
                        'observacao' => $obsUsuario,
                    ]);
                }
            }

            $pedido->load('itens');
            $pedido->event_type = $isNovo ? 'create' : 'update';
            $this->broadcastToNode('cozinha_novo_pedido', $pedido);

            return response()->json(['status' => 'sucesso']);

        } catch (\Exception $e) {
            Log::error('Erro ao adicionar item: ' . $e->getMessage());
            return response()->json(['status' => 'erro'], 500);
        }
    }

    /**
     * =========================================================================
     * GERENTE: EDIÇÃO FINA (QUANTIDADE)
     * =========================================================================
     */
    public function atualizarQuantidadeItem(Request $request, $id)
    {
        $validated = $request->validate([
            'quantidade' => 'required|integer',
        ]);

        try {
            $item = PedidoItem::findOrFail($id);
            $pedido = Pedido::with('itens')->findOrFail($item->pedido_id);

            $novaQtd = $validated['quantidade'];
            $qtdAtual = $item->quantidade;

            if ($novaQtd <= 0) return $this->removerItem($id);

            $diferenca = $novaQtd - $qtdAtual;
            $textoSmart = "Acrescentar + {$diferenca}";

            // PROTEÇÃO ZUMBI (Pronto)
            if ($pedido->status === 'pronto') {
                 if ($diferenca > 0) {
                     $novo = Pedido::create(['mesa' => $pedido->mesa, 'status' => 'pendente', 'user_id' => Auth::id()]);
                     $novo->itens()->create([
                         'nome_produto' => $item->nome_produto,
                         'quantidade' => $diferenca,
                         'preco' => $item->preco,
                         'categoria' => $item->categoria,
                         'observacao' => $textoSmart,
                     ]);
                     $novo->event_type = 'create';
                     $this->broadcastToNode('cozinha_novo_pedido', $novo->load('itens'));
                     return response()->json(['status' => 'sucesso']);
                 }
                 return response()->json(['status' => 'erro', 'msg' => 'Não pode reduzir pronto.']);
            }

            // ATUALIZAÇÃO EM PREPARO
            if ($diferenca > 0 && $pedido->status === 'preparo') {
                if (str_contains($item->observacao ?? '', 'Acrescentar +')) {
                    $item->quantidade = $novaQtd;
                    $item->observacao = "Acrescentar + {$novaQtd}";
                    $item->save();
                } 
                else {
                    $linhaAdicional = $pedido->itens()
                        ->where('nome_produto', $item->nome_produto)
                        ->where('observacao', 'like', 'Acrescentar +%')
                        ->first();

                    if ($linhaAdicional) {
                        $linhaAdicional->quantidade += $diferenca;
                        $linhaAdicional->observacao = "Acrescentar + {$linhaAdicional->quantidade}";
                        $linhaAdicional->save();
                    } else {
                        $pedido->itens()->create([
                            'nome_produto' => $item->nome_produto,
                            'quantidade' => $diferenca,
                            'preco' => $item->preco,
                            'categoria' => $item->categoria,
                            'observacao' => $textoSmart,
                        ]);
                    }
                }
            }
            else {
                // REDUÇÃO ou PENDENTE
                $item->quantidade = $novaQtd;
                if ($pedido->status === 'preparo' && str_contains($item->observacao ?? '', 'Acrescentar +')) {
                    $item->observacao = "Acrescentar + {$novaQtd}";
                }
                $item->save();
            }

            $pedido->load('itens'); 
            $pedido->event_type = 'update';
            
            if (in_array($pedido->status, ['pendente', 'preparo'])) {
                $this->broadcastToNode('cozinha_novo_pedido', $pedido);
            }

            return response()->json(['status' => 'sucesso']);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar quantidade: ' . $e->getMessage());
            return response()->json(['status' => 'erro'], 500);
        }
    }

    // --- MÉTODOS AUXILIARES ---

    public function listarAtivos() {
        return Pedido::with('itens')
            ->whereIn('status', ['pendente', 'preparo'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function atualizarStatus(Request $request, $id) {
        $validated = $request->validate([
            'status' => 'required|string|in:pendente,preparo,pronto',
        ]);

        $p = Pedido::findOrFail($id);
        $p->update(['status' => $validated['status']]);

        $ev = $validated['status'] === 'pronto' ? 'pedido_pronto' : 'cozinha_atualizar_status';
        $this->broadcastToNode($ev, ['id' => $p->id, 'status' => $validated['status'], 'mesa' => $p->mesa]);

        return response()->json(['status' => 'sucesso']);
    }

    public function cancelar($id) {
        $p = Pedido::findOrFail($id);

        if (!in_array($p->status, ['pendente', 'preparo'])) {
            return response()->json(['status' => 'erro', 'msg' => 'Só é possível cancelar pedidos pendentes ou em preparo.'], 422);
        }

        $p->update(['status' => 'cancelado']);

        $this->broadcastToNode('pedido_pronto', ['id' => $p->id, 'mesa' => $p->mesa]);
        return response()->json(['status' => 'sucesso']);
    }

    public function resumoMesas() {
        $pedidos = Pedido::with(['itens', 'garcom'])
            ->whereIn('status', ['pendente', 'preparo', 'pronto'])
            ->get()
            ->groupBy('mesa');

        $resumo = [];
        foreach ($pedidos as $mesa => $lista) {
            $total   = $lista->sum(fn($p) => $p->itens->sum(fn($i) => $i->preco * $i->quantidade));
            $garcons = $lista->pluck('garcom.name')->filter()->unique()->values()->toArray();
            $resumo[] = [
                'mesa'    => $mesa,
                'total'   => round($total, 2),
                'pedidos' => $lista->values(),
                'garcons' => $garcons,
            ];
        }
        return response()->json($resumo);
    }

    public function fecharMesa(Request $request, $mesa) {
        if (!is_numeric($mesa) || (int) $mesa < 1) {
            return response()->json(['status' => 'erro', 'msg' => 'Mesa inválida.'], 422);
        }

        $affected = Pedido::where('mesa', (int) $mesa)
            ->whereIn('status', ['pendente', 'preparo', 'pronto'])
            ->update(['status' => 'pago']);

        if ($affected === 0) {
            return response()->json(['status' => 'erro', 'msg' => 'Nenhum pedido ativo nesta mesa.'], 404);
        }

        $this->broadcastToNode('pedido_pago', ['mesa' => (int) $mesa]);
        return response()->json(['status' => 'sucesso']);
    }

    public function removerItem($id) {
        try {
            $item = PedidoItem::findOrFail($id);
            $pedidoId = $item->pedido_id;
            $item->delete();
            
            $pedido = Pedido::with('itens')->find($pedidoId);
            
            if (!$pedido || $pedido->itens->count() === 0) {
                $mesa = $pedido?->mesa;
                if($pedido) $pedido->update(['status' => 'cancelado']);
                $this->broadcastToNode('pedido_pronto', ['id' => $pedidoId, 'mesa' => $mesa]);
            } else {
                if (in_array($pedido->status, ['pendente', 'preparo'])) {
                    $pedido->event_type = 'update';
                    $this->broadcastToNode('cozinha_novo_pedido', $pedido);
                }
            }
            return response()->json(['status' => 'sucesso']);
        } catch (\Exception $e) { 
            return response()->json(['status' => 'erro'], 500); 
        }
    }

    public function listarMesasCadastradas()
    {
        return \App\Models\Mesa::where('ativa', true)
            ->orderBy('numero', 'asc')
            ->get();
    }

    /**
     * Envia eventos para o servidor Node.js
     * Agora utiliza variáveis de ambiente para suportar Deploy corretamente.
     */
    private function broadcastToNode($evento, $dados) {
        try {
            $nodeUrl = config('app.node_internal_url', 'http://127.0.0.1:3000');
            $endpoint = rtrim($nodeUrl, '/') . '/api/broadcast';
            $secret = config('app.broadcast_secret');

            Http::timeout(1)
                ->withToken($secret)
                ->post($endpoint, [
                    'evento' => $evento,
                    'dados' => $dados,
                ]);
        } catch (\Exception $e) {
            Log::warning("KDS Broadcast falhou: " . $e->getMessage());
        }
    }
}