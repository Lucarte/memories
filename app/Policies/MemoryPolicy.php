<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Memory;
use Illuminate\Auth\Access\Response;

class MemoryPolicy
{
    // true null vs. true false
    public function before(User $user)
    {
        return $user->isAdmin() ? true : null;
    }

    public function index()
    {
        return Response::allow('MemoryPolicy - index - allowed');
    }

    public function indexKid($kid)
    {
        return Response::allow('MemoryPolicy - indexKid - allowed');
    }

    public function show(User $user)
    {
        return $user->id !== null ? Response::allow('MemoryPolicy - show - allowed') : Response::deny('MemoryPolicy - show - denied');
    }

    public function create(User $user)
    {
        return $user !== null ? Response::allow('MemoryPolicy - create - allowed') : Response::deny('MemoryPolicy - create - denied');
    }

    public function delete(User $user, Memory $memory)
    {
        return $user->id === $memory->user_id ? Response::allow('MemoryPolicy - delete - allowed') : Response::deny('MemoryPolicy - delete - denied');
    }

    public function update(User $user, Memory $memory)
    {
        return $user->id === $memory->user_id ? Response::allow('MemoryPolicy - update - allowed') : Response::deny('MemoryPolicy - update - denied');
    }
}
