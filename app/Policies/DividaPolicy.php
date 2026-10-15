<?php

namespace App\Policies;

use App\Models\Divida;
use App\Models\User;

class DividaPolicy
{
    public function view(User $user, Divida $divida): bool
    {
        return $user->id === $divida->user_id;
    }

    public function update(User $user, Divida $divida): bool
    {
        return $this->view($user, $divida);
    }

    public function delete(User $user, Divida $divida): bool
    {
        return $this->view($user, $divida);
    }
}
