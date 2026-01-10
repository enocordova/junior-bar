<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProdutoController extends Controller
{
    public function index()
    {
        // Retorna direto do banco sem cache
        return Produto::where('ativo', true)
            ->orderBy('categoria')
            ->orderBy('nome')
            ->get();
    }
}