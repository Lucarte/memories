<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['user_id', 'memory_id', 'comment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function memory()
    {
        return $this->belongsTo(Memory::class);
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function toSearchableArray()
    {
        return [
            'user_id' => (int) $this->user_id,
            'memory_id' => (int) $this->memory_id,
            'comment' => $this->comment,
        ];
    }
}
