<?php

namespace App\Policies;

use App\Models\Parcela;
use App\Models\User;

class ParcelaPolicy
{
    public function update(User $user, Parcela $parcela): bool
    {
        if ($parcela->divida_id !== null) {
            return $user->id === $parcela->divida->user_id;
        }

        return $user->id === $parcela->fatura->cartaoCredito->user_id;
    }

    public function delete(User $user, Parcela $parcela): bool
    {
        return $this->update($user, $parcela);
    }
}
