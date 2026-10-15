<?php

namespace App\Policies;

use App\Models\Fatura;
use App\Models\User;

class FaturaPolicy
{
    public function view(User $user, Fatura $fatura): bool
    {
        return $user->id === $fatura->cartaoCredito->user_id;
    }

    public function update(User $user, Fatura $fatura): bool
    {
        return $this->view($user, $fatura);
    }

    public function delete(User $user, Fatura $fatura): bool
    {
        return $this->view($user, $fatura);
    }
}
