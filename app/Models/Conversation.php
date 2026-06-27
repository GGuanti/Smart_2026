<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['type', 'title', 'created_by', 'last_message_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /** Solo le conversazioni a cui partecipa l'utente. */
    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('users', fn ($q) => $q->where('users.id', $userId));
    }

    public function hasUser(int $userId): bool
    {
        return $this->users()->where('users.id', $userId)->exists();
    }
}
