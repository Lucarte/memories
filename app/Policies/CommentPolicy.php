<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Comment;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Perform pre-authorization checks.
     *
     * @param  \App\Models\User  $user
     * @return bool|null
     */
    public function before(User $user)
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Determine if the given comment can be created by the user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response
     */
    public function create(User $user)
    {
        return $user !== null
            ? Response::allow('CommentPolicy - create - allowed')
            : Response::deny('CommentPolicy - create - denied');
    }

    /**
     * Determine if the given comment can be updated by the user.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Auth\Access\Response
     */
    public function update(User $user, Comment $comment)
    {
        return $user->id === $comment->user_id
            ? Response::allow('CommentPolicy - update - allowed')
            : Response::deny('CommentPolicy - update - denied');
    }

    /**
     * Determine if the given comment can be deleted by the user.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Auth\Access\Response
     */
    public function delete(User $user, Comment $comment)
    {
        return $user->id === $comment->user_id
            ? Response::allow('CommentPolicy - delete - allowed')
            : Response::deny('CommentPolicy - delete - denied');
    }
}
