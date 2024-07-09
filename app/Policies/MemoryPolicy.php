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

    public function getAllMemories(User $user)
    {
        return $user->id !== null ? Response::allow('MemoryPolicy - getAllMemories - allowed') :  Response::deny('MemoryPolicy - getAllMemories - denied');
    }

    public function index($kid, User $user)
    {
        return $user->id !== null ? Response::allow('MemoryPolicy - index - allowed') :  Response::deny('MemoryPolicy - index - denied');
    }

    public function show(User $user)
    {
        return $user->id !== null ? Response::allow('MemoryPolicy - show - allowed') : Response::deny('MemoryPolicy - show - denied');
    }

    public function createWithFile(User $user)
    {
        return $user->isAdmin() ? Response::allow('MemoryPolicy - createWithFile - allowed') : Response::deny('MemoryPolicy - createWithFile - denied');
    }

    public function delete(User $user)
    {
        return $user->isAdmin() ? Response::allow('MemoryPolicy - delete - allowed') : Response::deny('MemoryPolicy - delete - denied');
    }

    public function update(User $user)
    {
        return $user->isAdmin() ? Response::allow('MemoryPolicy - update - allowed') : Response::deny('MemoryPolicy - update - denied');
    }
}
