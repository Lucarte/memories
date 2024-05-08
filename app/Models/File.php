<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'memory_id',
        'file_path',
    ];

    public function memory()
    {
        return $this->belongsTo(Memory::class, 'memory_id');
    }
}
