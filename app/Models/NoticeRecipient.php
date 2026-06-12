<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoticeRecipient extends Model
{
    protected $fillable = ['notice_id', 'user_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
