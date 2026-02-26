<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EntitySubscription extends Model
{
    protected $fillable = [
        'user_id',
        'subscribable_type',
        'subscribable_id',
        'channel',
        'subscribed_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsTo($userModel);
    }

    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForEntity(Builder $query, string $type, int $id): Builder
    {
        return $query->where('subscribable_type', $type)->where('subscribable_id', $id);
    }

    public static function isSubscribed(int $userId, string $type, int $id): bool
    {
        return static::where('user_id', $userId)
            ->where('subscribable_type', $type)
            ->where('subscribable_id', $id)
            ->exists();
    }
}
