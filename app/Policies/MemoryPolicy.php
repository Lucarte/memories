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

    public function createWithFile(User $user)
    {
        return null;
    }

    public function delete(User $user, Memory $memory)
    {
        return null;
    }

    public function update(User $user, Memory $memory)
    {
        return null;
    }
}
