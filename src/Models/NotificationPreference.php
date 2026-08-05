<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;

/**
 * A per-user, per-notification-type channel preference.
 *
 * @property int $user_id
 * @property string $notification_type
 * @property string $channel
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'channel',
    ];

    public function user(): BelongsTo
    {
        $userModel = Config::get('auth.providers.users.model', User::class);

        return $this->belongsTo($userModel);
    }
}
