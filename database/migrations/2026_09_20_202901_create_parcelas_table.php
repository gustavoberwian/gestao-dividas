<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fatura_id')->nullable()->constrained('faturas')->cascadeOnDelete();
            $table->foreignId('divida_id')->nullable()->constrained('dividas')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_parcela');
            $table->decimal('valor', 12, 2);
            $table->date('data_vencimento');
            $table->enum('status_pagamento', ['pendente', 'paga'])->default('pendente');
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE parcelas ADD CONSTRAINT parcelas_fatura_ou_divida_check CHECK (
                (fatura_id IS NOT NULL AND divida_id IS NULL)
                OR (fatura_id IS NULL AND divida_id IS NOT NULL)
            )
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcelas');
    }
};
