<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class MemoryPolicy
{
    // true null vs. true false
    public function before(User $user)
    {
        return $user->isAdmin() ? true : null;
    }

    public function index(User $user)
    {
        return $user->id !== null ? Response::allow('MemoryPolicy - index - allowed') :  Response::deny('MemoryPolicy - index - denied');
    }

    public function indexKid($kid, User $user)
    {
        return $user->id !== null ? Response::allow('MemoryPolicy - indexKid - allowed') :  Response::deny('MemoryPolicy - indexKid - denied');
    }

    public function show(User $user)
    {
        return $user->id !== null ? Response::allow('MemoryPolicy - show - allowed') : Response::deny('MemoryPolicy - show - denied');
    }

    public function createWithFile()
    {
        return null;
    }

    public function delete()
    {
        return null;
    }

    public function update()
    {
        return null;
    }
}
