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
        Schema::create('cartoes_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nome_cartao');
            $table->decimal('limite', 12, 2);
            $table->unsignedSmallInteger('dia_fechamento');
            $table->unsignedSmallInteger('dia_vencimento');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE cartoes_credito ADD CONSTRAINT cartoes_credito_dia_fechamento_check CHECK (dia_fechamento BETWEEN 1 AND 31)');
        DB::statement('ALTER TABLE cartoes_credito ADD CONSTRAINT cartoes_credito_dia_vencimento_check CHECK (dia_vencimento BETWEEN 1 AND 31)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartoes_credito');
    }
};
