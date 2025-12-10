<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produto;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $itens = [
            ['nome' => 'Hambúrguer', 'categoria' => 'Lanches', 'preco' => 25.00],
            ['nome' => 'X-Frango', 'categoria' => 'Lanches', 'preco' => 28.00],
            ['nome' => 'X-tudo', 'categoria' => 'Lanches', 'preco' => 32.00],
            ['nome' => 'Batata Frita', 'categoria' => 'Acompanhamentos', 'preco' => 15.00],
            ['nome' => 'Coca-Cola Lata', 'categoria' => 'Bebidas', 'preco' => 6.00],
            ['nome' => 'Água sem Gás', 'categoria' => 'Bebidas', 'preco' => 4.00],
        ];

        foreach ($itens as $item) {
            Produto::create($item);
        }
    }
}