<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_items', function (Blueprint $table) {
            $table->tinyInteger('rodada')->unsigned()->default(1)->after('categoria');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->json('rodadas_concluidas')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pedido_items', function (Blueprint $table) {
            $table->dropColumn('rodada');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('rodadas_concluidas');
        });
    }
};
