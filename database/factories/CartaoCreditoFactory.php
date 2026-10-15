<?php

namespace Database\Factories;

use App\Models\CartaoCredito;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CartaoCredito> */
class CartaoCreditoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nome_cartao' => 'Cartão '.$this->faker->word(),
            'limite' => $this->faker->randomFloat(2, 500, 20000),
            'dia_fechamento' => 3,
            'dia_vencimento' => 10,
        ];
    }
}
