<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    public function before(User $user)
    {
        return $user->isAdmin() ? true : null;
    }

    public function show(User $user)
    {
        // Allow files to be shown
        return true;
    }

    public function delete(User $user, File $file)
    {
        // Deny deletion
        return false;
    }

    public function update(User $user, File $file)
    {
        // Deny update
        return false;
    }
}
