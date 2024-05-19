<?php

namespace App\Policies;

use App\Models\Url;
use App\Models\User;

class UrlPolicy
{
    public function before(User $user)
    {
        return $user->isAdmin() ? true : null;
    }

    public function show(User $user)
    {
        // Allow urls to be shown
        return true;
    }

    public function delete(User $user, Url $url)
    {
        // Deny deletion
        return false;
    }

    public function update(User $user, Url $url)
    {
        // Deny update
        return false;
    }
}
