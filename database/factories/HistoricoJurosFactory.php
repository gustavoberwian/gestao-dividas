<?php

namespace Database\Factories;

use App\Models\Divida;
use App\Models\HistoricoJuros;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HistoricoJuros> */
class HistoricoJurosFactory extends Factory
{
    public function definition(): array
    {
        return [
            'divida_id' => Divida::factory(),
            'mes_referencia' => now()->startOfMonth()->toDateString(),
            'taxa_juros' => 0.015,
            'saldo_devedor_calculado' => 0,
        ];
    }
}
