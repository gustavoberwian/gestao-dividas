<?php

namespace App\Policies;

use App\Models\HistoricoJuros;
use App\Models\User;

class HistoricoJurosPolicy
{
    public function update(User $user, HistoricoJuros $historicoJuros): bool
    {
        return $user->id === $historicoJuros->divida->user_id;
    }

    public function delete(User $user, HistoricoJuros $historicoJuros): bool
    {
        return $this->update($user, $historicoJuros);
    }
}
