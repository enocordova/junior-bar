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
        Schema::table('users', function (Blueprint $table) {
            // Cria a coluna 'role' que aceita apenas estes 3 valores
            // 'default' garante que se ninguém escolher, será 'garcom'
            $table->enum('role', ['admin', 'garcom', 'cozinha'])->default('garcom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove a coluna caso você desfaça a migração (rollback)
            $table->dropColumn('role');
        });
    }
};