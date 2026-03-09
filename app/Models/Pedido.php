<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;
    protected $table = 'pedidos'; 
    protected $fillable = ['mesa', 'status', 'user_id'];

    public function itens()
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function garcom()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}