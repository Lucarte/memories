<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;
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

    protected $appends = ['avatar_path']; // Append avatar_path

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
    
    // Add the accessor for avatar_path
    public function getAvatarPathAttribute()
    {
        // Check if the user has an associated avatar
        if ($this->avatar) {
            // Manually construct the URL for the avatar stored in DigitalOcean Spaces
            return env('DO_SPACES_ENDPOINT') . '/' . env('DO_SPACES_BUCKET') . '/' . $this->avatar->avatar_path;
        }
        return null; // Return null if there's no avatar
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
