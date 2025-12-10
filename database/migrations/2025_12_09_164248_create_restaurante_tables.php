<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Note que aqui não tem "class NomeDaClasse", é apenas "return new class"
return new class extends Migration
{
    public function up()
    {
        // 1. Cria tabela PEDIDOS
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('mesa');
            $table->string('status')->default('pendente');
            $table->timestamps();
        });

        // 2. Cria tabela PEDIDO_ITEMS
        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            $table->string('nome_produto');
            $table->integer('quantidade');
            $table->string('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pedido_items');
        Schema::dropIfExists('pedidos');
    }
};