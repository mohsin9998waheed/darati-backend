<?php

namespace App\Policies;

use App\Models\Audiobook;
use App\Models\User;

class AudiobookPolicy
{
    public function update(User $user, Audiobook $audiobook): bool
    {
        return $user->id === $audiobook->artist_id || $user->isAdmin();
    }

    public function delete(User $user, Audiobook $audiobook): bool
    {
        return $user->id === $audiobook->artist_id || $user->isAdmin();
    }
}
