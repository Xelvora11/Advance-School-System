<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageThreadParticipant extends Model
{
    protected $fillable = ['thread_id', 'user_id', 'last_read_at'];

    protected function casts(): array
    {
        return ['last_read_at' => 'datetime'];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function unreadCountForUser(int $userId): int
    {
        return self::where('user_id', $userId)
            ->whereHas('thread.messages', function ($query) use ($userId) {
                $query->where('sender_id', '!=', $userId)
                    ->where(function ($inner) {
                        $inner->whereNull('message_thread_participants.last_read_at')
                            ->orWhereColumn('messages.created_at', '>', 'message_thread_participants.last_read_at');
                    });
            })
            ->count();
    }
}
