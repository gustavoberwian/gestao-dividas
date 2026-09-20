<?php

namespace App\Policies;

use App\Models\CartaoCredito;
use App\Models\User;

class CartaoCreditoPolicy
{
    public function view(User $user, CartaoCredito $cartaoCredito): bool
    {
        return $user->id === $cartaoCredito->user_id;
    }
}
