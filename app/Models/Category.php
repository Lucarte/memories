<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['category'];

    public function memories()
    {
        return $this->belongsToMany(Memory::class);
    }

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

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('memories');
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Include related memories in the searchable array
        $array['memories'] = $this->memories ? $this->memories->toArray() : [];

        return $array;
    }
}
