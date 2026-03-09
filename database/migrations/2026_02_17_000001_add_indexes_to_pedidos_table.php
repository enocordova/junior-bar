<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->index('status');
            $table->index('mesa');
            $table->index(['mesa', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['mesa']);
            $table->dropIndex(['mesa', 'status']);
        });
    }
};
