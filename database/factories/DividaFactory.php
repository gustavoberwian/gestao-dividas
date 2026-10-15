<?php

namespace Database\Factories;

use App\Models\Divida;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Divida> */
class DividaFactory extends Factory
{
    public function definition(): array
    {
        $valor = $this->faker->randomFloat(2, 500, 50000);

        return [
            'user_id' => User::factory(),
            'descricao' => 'Empréstimo '.$this->faker->word(),
            'valor_principal' => $valor,
            'credor' => $this->faker->company(),
            'data_inicio' => '2026-01-01',
            'saldo_devedor_atual' => $valor,
        ];
    }

    /** Divida com valor principal (e saldo inicial) definido. */
    public function valor(float $valor): static
    {
        return $this->state(['valor_principal' => $valor, 'saldo_devedor_atual' => $valor]);
    }
}
