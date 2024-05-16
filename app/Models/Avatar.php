<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Avatar extends Model
{
    use HasFactory;

    protected $fillable = [
        'avatar_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
