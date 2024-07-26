<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Memory extends Model
{
    use HasFactory, Searchable;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $model->searchable();
        });

        static::updated(function ($model) {
            $model->searchable();
        });

        static::deleted(function ($model) {
            $model->unsearchable();
        });
    }

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
        return $query->with([
            'files',
            'categories',
            'urls',
            'user',
        ]);
    }

    public function toSearchableArray()
    {
        // Customize the array returned to MeiliSearch
        $array = $this->toArray();

        // Ensure relationships are properly formatted
        $array['user'] = $this->user ? $this->user->toArray() : null;
        $array['files'] = $this->files ? $this->files->toArray() : [];
        $array['categories'] = $this->categories ? $this->categories->toArray() : [];
        $array['urls'] = $this->urls ? $this->urls->toArray() : [];

        return $array;
    }
}
