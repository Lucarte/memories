<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Searchable;

    protected $guarded = ['password'];

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'password_confirmation',
        'relationship_to_kid',
        'terms',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }

    public function avatar()
    {
        return $this->hasOne(Avatar::class);
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('avatar');
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();
        $array['avatar'] = $this->avatar ? $this->avatar->toArray() : null;

        return $array;
    }
}
