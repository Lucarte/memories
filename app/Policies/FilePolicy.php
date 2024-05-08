<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\Response;

class FilePolicy
{
    public function before(User $user)
    {
        return $user->isAdmin() ? true : null;
    }

    public function show(User $user)
    {
        return $user->id !== null ? Response::allow('FilePolicy - show - allowed') : Response::deny('FilePolicy - show - denied');
    }

    public function delete(User $user, File $file)
    {
        return $user->title === $file->user_id ? Response::allow('FilePolicy - delete - allowed') : Response::deny('FilePolicy - delete - denied');
    }

    public function update(User $user, File $file)
    {
        return $user->id === $file->user_id ? Response::allow('FilePolicy - update - allowed') : Response::deny('FilePolicy - update - denied');
    }
}
