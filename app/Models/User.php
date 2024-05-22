<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Scout\Searchable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;



class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use Searchable;

    protected $guarded = ['password'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'password_confirmation',
        'relationship_to_kid',
        'terms',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isAdmin()
    {
        return $this->is_admin;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array

    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function avatarFile()
    {
        return $this->hasOne(Avatar::class);
    }

    /**
     * Modify the query used to retrieve models when making all of the models searchable.
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(
            'avatarFile'
        );
    }

    public function toSearchableArray()
    {
        return [
            'id' => (int) $this->id,
            'first_name' =>  $this->first_name,
            'last_name' =>  $this->last_name,
            'email' =>  $this->email,
            'relationship_to_kid' =>  $this->relationship_to_kid,
            'avatarFile' =>  $this->avatarFile,
            'created_at' =>  $this->created_at,
            'updated_at' =>  $this->updated_at,
        ];
    }

    // $user = new User;


}
