<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model
{
    use HasFactory;

    protected $table = 'pedido_items';
    
    // Campos que podem ser preenchidos
    protected $fillable = [
        'pedido_id', 
        'nome_produto', 
        'quantidade', 
        'observacao',
        'preco',
        'categoria'
    ];

    protected $casts = [
        'preco' => 'float',
        'quantidade' => 'integer'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}