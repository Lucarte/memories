<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Url extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'url_address',
    ];

    // Define the inverse relationship to the File model
    public function memory()
    {
        return $this->belongsTo(Memory::class);
    }

    public function toSearchableArray()
    {
        return [
            'url_address' =>  $this->url_address,
        ];
    }
}
