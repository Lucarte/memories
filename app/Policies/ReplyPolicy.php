<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReplyPolicy
{
    public function before(User $user)
    {
        return $user->isAdmin() ? true : null;
    }

    public function create(User $user)
    {
        return $user !== null ? Response::allow('ReplyPolicy - create - allowed') : Response::deny('ReplyPolicy - create - denied');
    }

    public function delete(User $user)
    {
        return $user !== null ? Response::allow('ReplyPolicy - delete - allowed') : Response::deny('ReplyPolicy - delete - denied');
    }

    public function update(User $user)
    {
        return $user !== null ? Response::allow('ReplyPolicy - update - allowed') : Response::deny('ReplyPolicy - update - denied');
    }
}
