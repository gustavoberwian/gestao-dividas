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
        Schema::create('faturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartao_credito_id')->constrained('cartoes_credito')->cascadeOnDelete();
            $table->date('mes_referencia');
            $table->decimal('valor_total', 12, 2);
            $table->enum('status_pagamento', ['aberta', 'paga'])->default('aberta');
            $table->timestamps();

            $table->unique(['cartao_credito_id', 'mes_referencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faturas');
    }
};
