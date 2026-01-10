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
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->integer('numero')->unique(); // Ex: 1, 2, 50
            $table->string('label')->nullable(); // Ex: "VIP 01", "Varanda"
            $table->integer('capacidade')->default(4); // Quantas pessoas cabem
            $table->boolean('ativa')->default(true); // Se quebrar, desativa
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};