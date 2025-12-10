<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model
{
    use HasFactory;
    protected $table = 'pedido_items';
    protected $fillable = ['pedido_id', 'nome_produto', 'quantidade', 'observacao'];
}