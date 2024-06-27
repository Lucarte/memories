<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use Searchable;

    protected $fillable = [
        'file_path',
        'user_id',
        'memory_id',
    ];

    public function memory()
    {
        return $this->belongsTo(Memory::class, 'memory_id');
    }

    public function toSearchableArray()
    {
        return [
            'file_path' =>  $this->file_path,
            'user_id' => (int) $this->user_id,
            'memory_id' => (int) $this->memory_id,
        ];
    }
}
