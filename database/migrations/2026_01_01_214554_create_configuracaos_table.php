<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            
            // Estrutura Key-Value para suportar o Seeder e o Model atuais
            $table->string('grupo')->nullable()->index(); // Ex: 'Identidade', 'Financeiro'
            $table->string('titulo')->nullable();         // Ex: 'Nome do Restaurante'
            $table->string('chave')->unique()->index();   // Ex: 'nome_restaurante'
            $table->text('valor')->nullable();            // Ex: 'Junior BAR'
            $table->string('tipo')->default('text');      // Ex: 'text', 'number', 'boolean'
            $table->text('descricao')->nullable();        // Explicação do campo
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};