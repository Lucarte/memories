<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    public function before(User $user)
    {
        return $user->isAdmin() ? true : null;
    }

    public function create(User $user)
    {
        return $user !== null ? Response::allow('CommentPolicy - create - allowed') : Response::deny('CommentPolicy - create - denied');
    }

    public function delete(User $user)
    {
        return $user !== null ? Response::allow('CommentPolicy - delete - allowed') : Response::deny('CommentPolicy - delete - denied');
    }

    public function update(User $user)
    {
        return $user !== null ? Response::allow('CommentPolicy - update - allowed') : Response::deny('CommentPolicy - update - denied');
    }
}
