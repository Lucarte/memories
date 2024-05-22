<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reply extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['user_id', 'comment_id', 'reply', 'parent_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comment()
    {
        return $this->belongsTo(Memory::class);
    }

    public function parent()
    {
        return $this->belongsTo(Reply::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Reply::class, 'parent_id');
    }

    public function toSearchableArray()
    {
        return [
            'reply' =>  $this->reply,
            'user_id' => (int)  $this->user_id,
            'comment_id' => (int)  $this->comment_id,
            'parent_id' => (int)  $this->parent_id,
        ];
    }
}
