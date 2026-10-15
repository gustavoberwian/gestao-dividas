<?php

namespace Database\Factories;

use App\Models\Divida;
use App\Models\Fatura;
use App\Models\Parcela;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Por padrao a parcela pertence a uma divida (o CHECK do banco exige exatamente um de
 * divida_id/fatura_id). Use daFatura() para uma parcela de fatura.
 *
 * @extends Factory<Parcela>
 */
class ParcelaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'divida_id' => Divida::factory(),
            'fatura_id' => null,
            'numero_parcela' => 1,
            'valor' => $this->faker->randomFloat(2, 50, 2000),
            'data_vencimento' => now()->toDateString(),
            'status_pagamento' => 'pendente',
        ];
    }

    public function paga(): static
    {
        return $this->state(['status_pagamento' => 'paga']);
    }

    public function daFatura(?Fatura $fatura = null): static
    {
        return $this->state(['divida_id' => null, 'fatura_id' => $fatura ?? Fatura::factory()]);
    }
}
