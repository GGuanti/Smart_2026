<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GridLayout extends Model
{
    protected $fillable = ['user_id', 'query_name', 'layout'];
    protected $casts    = ['layout' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function forUser(int $userId, string $queryName): ?array
    {
        return static::where('user_id', $userId)
            ->where('query_name', $queryName)
            ->value('layout');
    }
}
