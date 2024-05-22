<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['category'];

    public function memories()
    {
        return $this->belongsToMany(Memory::class);
    }

    public function toSearchableArray()
    {
        return [
            'category' =>  $this->category,
        ];
    }
}
