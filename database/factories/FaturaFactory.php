<?php

namespace Database\Factories;

use App\Models\CartaoCredito;
use App\Models\Fatura;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Fatura> */
class FaturaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cartao_credito_id' => CartaoCredito::factory(),
            'mes_referencia' => now()->startOfMonth()->toDateString(),
            'valor_total' => $this->faker->randomFloat(2, 50, 5000),
            'status_pagamento' => 'aberta',
        ];
    }

    public function paga(): static
    {
        return $this->state(['status_pagamento' => 'paga']);
    }
}
