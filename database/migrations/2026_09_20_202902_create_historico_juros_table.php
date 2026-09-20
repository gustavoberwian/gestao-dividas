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
        Schema::create('historico_juros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('divida_id')->constrained('dividas')->cascadeOnDelete();
            $table->date('mes_referencia');
            $table->decimal('taxa_juros', 6, 4);
            $table->decimal('saldo_devedor_calculado', 12, 2);
            $table->timestamps();

            $table->unique(['divida_id', 'mes_referencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_juros');
    }
};
