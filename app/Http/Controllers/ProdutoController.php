<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProdutoController extends Controller
{
    public function index()
    {
        // Cacheia o cardápio por 60 minutos (ou até ser limpo)
        // Isso deixa o carregamento instantâneo.
        return Cache::remember('cardapio_ativo', 3600, function () {
            return Produto::where('ativo', true)
                ->orderBy('categoria')
                ->orderBy('nome')
                ->get();
        });
    }
}