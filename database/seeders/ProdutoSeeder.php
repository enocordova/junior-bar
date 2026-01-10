<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produto;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $itens = [
            ['nome' => 'Hambúrguer', 'categoria' => 'Lanches', 'preco' => 25.00, 'ativo' => true],
            ['nome' => 'X-Frango', 'categoria' => 'Lanches', 'preco' => 28.00, 'ativo' => true],
            ['nome' => 'X-tudo', 'categoria' => 'Lanches', 'preco' => 32.00, 'ativo' => true],
            ['nome' => 'Batata Frita', 'categoria' => 'Acompanhamentos', 'preco' => 15.00, 'ativo' => true],
            ['nome' => 'Coca-Cola Lata', 'categoria' => 'Bebidas', 'preco' => 6.00, 'ativo' => true],
            ['nome' => 'Água sem Gás', 'categoria' => 'Bebidas', 'preco' => 4.00, 'ativo' => true],
        ];

        foreach ($itens as $item) {
            // Verifica pelo 'nome'; se não existir, cria com os dados do $item
            Produto::firstOrCreate(
                ['nome' => $item['nome']], 
                $item
            );
        }
    }
}