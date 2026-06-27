<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{

public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(MessageAttachment::class);
}

/**
 * Quando un messaggio viene eliminato, cancella anche i file fisici dal disco.
 * (Vale per la cancellazione via Eloquent, es. il pulsante "elimina".)
 */
protected static function booted(): void
{
    static::deleting(function (Message $message) {
        foreach ($message->attachments as $attachment) {
            \Illuminate\Support\Facades\Storage::delete($attachment->path);
        }
    });
}
    protected $fillable = ['conversation_id', 'user_id', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
