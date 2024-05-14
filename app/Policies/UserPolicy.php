<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function before(User $user, $ability)
    {
        // Fans can perform update, getById, and delete for themselves only
        if (in_array($ability, ['update', 'getById', 'delete'])) {
            $userId = (int) request()->route('id');
            if ($userId === $user->id) {
                return Response::allow('UserPolicy - allowed');
            }
        }

        // Admin can do all CRUD Ops for all
        return $user->isAdmin() ? Response::allow('UserPolicy - admin - allowed') : null;
    }

    public function index(User $user)
    {
        return $user->isAdmin() ? Response::allow('UserPolicy - index - allowed') : Response::deny('UserPolicy - index - denied');
    }

    public function delete(User $user, $id)
    {
        return $user->id === $id ? Response::allow('UserPolicy - delete - allowed') : Response::deny('UserPolicy - delete - denied');
    }

    public function getById(User $user, $id)
    {
        return $user->id === $id ? Response::allow('UserPolicy - get - allowed') : Response::deny('UserPolicy - get - denied');
    }

    public function update(User $user, $id)
    {
        return $user->id === $id ? Response::allow('UserPolicy - update - allowed') : Response::deny('UserPolicy - update - denied');
    }
}
