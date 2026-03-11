<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_items', function (Blueprint $table) {
            $table->enum('status', ['pendente', 'pronto'])->default('pendente')->after('rodada');
        });
    }

    public function down(): void
    {
        Schema::table('pedido_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
