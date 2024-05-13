<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'file_path',
        'user_id',
        'memory_id',
        'file_type',
    ];

    public function memory()
    {
        return $this->belongsTo(Memory::class, 'memory_id');
    }
}
