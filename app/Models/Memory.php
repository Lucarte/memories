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

    protected $fillable = [
        'title',
        'description',
        'kid',
        'year',
        'month',
        'day',
        'memory_date',
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
        return $this->belongsToMany(Category::class, 'category_memory', 'memory_id', 'category_id');
    }

    public function urls()
    {
        return $this->hasMany(Url::class, 'memory_id');
    }

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
        // Convert the model instance to an array
        $array = $this->toArray();

        // Add memory_date to the searchable array
        $array['memory_date'] = $this->memory_date;

        // Add related models as well
        $array['user'] = $this->user ? $this->user->toArray() : null;
        $array['files'] = $this->files ? $this->files->toArray() : [];
        $array['categories'] = $this->categories ? $this->categories->toArray() : [];
        $array['urls'] = $this->urls ? $this->urls->toArray() : [];

        return $array;
    }
}
