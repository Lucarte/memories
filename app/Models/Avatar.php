<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Avatar extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'avatar_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    /**
     * Modify the query used to retrieve models when making all of the models searchable.
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(
            'user'
        );
    }
    public function toSearchableArray()
    {
        return [
            'avatar_path' =>  $this->avatar_path,
        ];
    }
}
