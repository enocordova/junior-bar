<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                        'mesa' => $validated['mesa'],
                        'status' => 'pendente'
                    ]);
                    $isNovoPedido = true;
                }

                // 2. Processamento dos Itens
                foreach ($validated['itens'] as $item) {
                    $produtoReal = $produtosDb[$item['id_produto']];
                    $obsItem = $item['obs'] ?? null;

                    // --- CENÁRIO: RASCUNHO (Pendente) ---
                    // Funde tudo silenciosamente. 1 Batata + 3 Batatas vira "4 Batatas" na mesma linha.
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
                        // Procura se JÁ EXISTE uma linha de acréscimo "viva" para este produto
                        $linhaAdicional = $pedido->itens()
                            ->where('nome_produto', $produtoReal->nome)
                            ->where('observacao', 'like', 'Acrescentar +%') // A chave da fusão
                            ->latest()
                            ->first();

                        if ($linhaAdicional) {
                            // FUSÃO: Soma o novo pedido (ex: 2) ao que já era acréscimo (ex: 3)
                            $novaQtdTotal = $linhaAdicional->quantidade + $item['qtd'];
                            
                            $linhaAdicional->quantidade = $novaQtdTotal;
                            // Atualiza o texto para refletir o TOTAL do acréscimo
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
            Log::error('Erro: ' . $e->getMessage());
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
        $request->validate(['mesa' => 'required|integer', 'produto_id' => 'required|exists:produtos,id']);

        try {
            $produto = Produto::findOrFail($request->produto_id);
            
            $pedido = Pedido::where('mesa', $request->mesa)
                ->whereIn('status', ['pendente', 'preparo'])
                ->latest()->first();

            $isNovo = false;
            if (!$pedido) {
                $pedido = Pedido::create(['mesa' => $request->mesa, 'status' => 'pendente']);
                $isNovo = true;
            }

            if ($pedido->status === 'preparo') {
                // Tenta fundir com acréscimo existente
                $linhaAdicional = $pedido->itens()
                    ->where('nome_produto', $produto->nome)
                    ->where('observacao', 'like', 'Acrescentar +%')
                    ->first();

                if ($linhaAdicional) {
                    $linhaAdicional->quantidade += 1;
                    $linhaAdicional->observacao = "Acrescentar + {$linhaAdicional->quantidade}";
                    $linhaAdicional->save();
                } else {
                    $pedido->itens()->create([
                        'nome_produto' => $produto->nome,
                        'quantidade' => 1,
                        'preco' => $produto->preco,
                        'categoria' => $produto->categoria,
                        'observacao' => 'Acrescentar + 1'
                    ]);
                }
            } else {
                // Pendente: Soma ou cria
                $query = $pedido->itens()->where('nome_produto', $produto->nome)->whereNull('observacao');
                $existente = $query->first();
                if ($existente) {
                    $existente->quantidade += 1;
                    $existente->save();
                } else {
                    $pedido->itens()->create([
                        'nome_produto' => $produto->nome,
                        'quantidade' => 1,
                        'preco' => $produto->preco,
                        'categoria' => $produto->categoria,
                        'observacao' => null
                    ]);
                }
            }

            $pedido->load('itens'); 
            $pedido->event_type = $isNovo ? 'create' : 'update';
            $this->broadcastToNode('cozinha_novo_pedido', $pedido);

            return response()->json(['status' => 'sucesso']);

        } catch (\Exception $e) {
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
        try {
            $item = PedidoItem::findOrFail($id);
            $pedido = Pedido::with('itens')->findOrFail($item->pedido_id);
            
            $novaQtd = $request->input('quantidade');
            $qtdAtual = $item->quantidade;

            if ($novaQtd <= 0) return $this->removerItem($id);

            $diferenca = $novaQtd - $qtdAtual;
            $textoSmart = "Acrescentar + {$diferenca}";

            // PROTEÇÃO ZUMBI (Pronto)
            if ($pedido->status === 'pronto') {
                 if ($diferenca > 0) {
                     $novo = Pedido::create(['mesa' => $pedido->mesa, 'status' => 'pendente']);
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

            // ATUALIZAÇÃO EM PREPARO (Smart Merge)
            if ($diferenca > 0 && $pedido->status === 'preparo') {
                
                // Se o item que estamos clicando JÁ É uma linha de "Acrescentar mais"
                // Apenas atualizamos ela.
                if (str_contains($item->observacao, 'Acrescentar +')) {
                    $item->quantidade = $novaQtd;
                    $item->observacao = "Acrescentar + {$novaQtd}";
                    $item->save();
                } 
                else {
                    // Se estamos clicando no item ORIGINAL, não mexemos nele (histórico).
                    // Procuramos se já existe um "filho" de acréscimo para somar nele.
                    $linhaAdicional = $pedido->itens()
                        ->where('nome_produto', $item->nome_produto)
                        ->where('observacao', 'like', 'Acrescentar +%')
                        ->first();

                    if ($linhaAdicional) {
                        $linhaAdicional->quantidade += $diferenca;
                        $linhaAdicional->observacao = "Acrescentar + {$linhaAdicional->quantidade}";
                        $linhaAdicional->save();
                    } else {
                        // Cria novo acréscimo
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
                // 3. REDUÇÃO ou PENDENTE (Onde estava o erro!)
                $item->quantidade = $novaQtd;

                if ($pedido->status === 'preparo' && str_contains($item->observacao, 'Acrescentar +')) {
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
            return response()->json(['status' => 'erro'], 500);
        }
    }

    // --- MÉTODOS AUXILIARES (Inalterados) ---
    public function listarAtivos() {
        return Pedido::with('itens')->whereIn('status', ['pendente', 'preparo'])->orderBy('created_at', 'asc')->get();
    }
    public function atualizarStatus(Request $request, $id) {
        $p = Pedido::findOrFail($id); $p->update(['status' => $request->status]);
        $ev = $request->status === 'pronto' ? 'pedido_pronto' : 'cozinha_atualizar_status';
        $this->broadcastToNode($ev, ['id' => $p->id, 'status' => $request->status, 'mesa' => $p->mesa]);
        return response()->json(['status' => 'sucesso']);
    }
    public function cancelar($id) {
        $p = Pedido::findOrFail($id); $p->update(['status' => 'cancelado']);
        $this->broadcastToNode('pedido_pronto', ['id' => $id]);
        return response()->json(['status' => 'sucesso']);
    }
    public function resumoMesas() {
        $pedidos = Pedido::with('itens')->whereIn('status', ['pendente', 'preparo', 'pronto'])->get()->groupBy('mesa');
        $resumo = [];
        foreach ($pedidos as $mesa => $lista) {
            $total = $lista->sum(fn($p) => $p->itens->sum(fn($i) => $i->preco * $i->quantidade));
            $resumo[] = ['mesa' => $mesa, 'total' => round($total, 2), 'pedidos' => $lista->values()];
        }
        return response()->json($resumo);
    }
    public function fecharMesa($mesa) {
        Pedido::where('mesa', $mesa)->whereIn('status', ['pendente', 'preparo', 'pronto'])->update(['status' => 'pago']);
        $this->broadcastToNode('pedido_pago', ['mesa' => $mesa]);
        return response()->json(['status' => 'sucesso']);
    }
    public function removerItem($id) {
        try {
            $item = PedidoItem::findOrFail($id);
            $pedidoId = $item->pedido_id;
            $item->delete();
            $pedido = Pedido::with('itens')->find($pedidoId);
            if (!$pedido || $pedido->itens->count() === 0) {
                if($pedido) $pedido->update(['status' => 'cancelado']);
                $this->broadcastToNode('pedido_pronto', ['id' => $pedidoId]);
            } else {
                if (in_array($pedido->status, ['pendente', 'preparo'])) {
                    $pedido->event_type = 'update';
                    $this->broadcastToNode('cozinha_novo_pedido', $pedido);
                }
            }
            return response()->json(['status' => 'sucesso']);
        } catch (\Exception $e) { return response()->json(['status' => 'erro'], 500); }
    }
    private function broadcastToNode($evento, $dados) {
        try { Http::timeout(1)->post('http://127.0.0.1:3000/api/broadcast', ['evento' => $evento, 'dados' => $dados]); } catch (\Exception $e) {}
    }

    /**
     * Retorna a lista oficial de mesas cadastradas no Filament
     */
    public function listarMesasCadastradas()
    {
        // Retorna apenas mesas marcadas como 'ativa', ordenadas pelo número
        return \App\Models\Mesa::where('ativa', true)
            ->orderBy('numero', 'asc')
            ->get();
    }
}