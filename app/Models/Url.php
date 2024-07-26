<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Url extends Model
{
    use HasFactory;
    use Searchable;

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
        'url_address',
        'memory_id',
    ];

    // Define the inverse relationship to the File model
    public function memory()
    {
        return $this->belongsTo(Memory::class);
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();
        $array['memory'] = $this->memory ? $this->memory->toArray() : null;

        return $array;
    }
}
