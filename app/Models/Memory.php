<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Memory extends Model
{
    use HasFactory;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'kid',
        'year',
        'month',
        'day',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'memory_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function urls()
    {
        return $this->hasMany(Url::class, 'memory_id');
    }

    /**
     * Modify the query used to retrieve models when making all of the models searchable.
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(
            [
                'files',
                'categories',
                'urls',
                'user',
            ]
        );
    }

    public function toSearchableArray()
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'title' => $this->title,
            'description' =>  $this->description,
            'kid' =>  $this->kid,
            'year' => (int)  $this->year,
            'month' =>  $this->month,
            'day' =>  (int) $this->day,
            'categories' =>  $this->categories,
            'files' =>  $this->files,
            'urls' =>  $this->urls,
            'user' =>  $this->user,
        ];
    }
}
