<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;

class AdminNotification extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'icon',
        'icon_color',
        'notification_type',
        'url',
        'delivery_method',
        'created_by',
        'source',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the admin user who created this notification
     */
    public function creator(): BelongsTo
    {
        $userModel = Config::get('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'created_by');
    }

    /**
     * Get all recipients (users) for this notification
     */
    public function recipients(): BelongsToMany
    {
        $userModel = Config::get('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsToMany($userModel, 'notification_recipients', 'admin_notification_id', 'user_id')
            ->withPivot('read_at')
            ->withTimestamps()
            ->orderBy('notification_recipients.created_at', 'desc');
    }

    /**
     * Get only recipients who have read the notification
     */
    public function readRecipients(): BelongsToMany
    {
        return $this->recipients()->wherePivotNotNull('read_at');
    }

    /**
     * Get only recipients who have NOT read the notification
     */
    public function unreadRecipients(): BelongsToMany
    {
        return $this->recipients()->wherePivotNull('read_at');
    }

    /**
     * Check if notification has been sent
     */
    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    /**
     * Check if notification is still a draft
     */
    public function isDraft(): bool
    {
        return $this->sent_at === null;
    }

    /**
     * Get count of recipients who have read the notification
     */
    public function getReadCountAttribute(): int
    {
        return $this->readRecipients()->count();
    }

    /**
     * Get count of recipients who have NOT read the notification
     */
    public function getUnreadCountAttribute(): int
    {
        return $this->unreadRecipients()->count();
    }

    /**
     * Get total count of all recipients
     */
    public function getTotalRecipientsAttribute(): int
    {
        return $this->recipients()->count();
    }

    /**
     * Send this notification to all attached recipients.
     * For email-only delivery, auto-marks pivot read_at since there is no bell to click.
     */
    public function sendToRecipients(): void
    {
        $this->update(['sent_at' => now()]);

        foreach ($this->recipients as $user) {
            $user->notify(new \Zynqa\FilamentNotifications\Notifications\AdminBroadcastNotification($this));
        }

        // Email has no read-tracking mechanism; mark pivot as read immediately on send
        if ($this->delivery_method === 'email') {
            \Illuminate\Support\Facades\DB::table('notification_recipients')
                ->where('admin_notification_id', $this->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }
    }

    /**
     * Scope a query to only include sent notifications
     */
    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    /**
     * Scope a query to only include draft notifications
     */
    public function scopeDraft($query)
    {
        return $query->whereNull('sent_at');
    }

    /**
     * Scope a query to order by most recent
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get icon color options
     */
    public static function getIconColorOptions(): array
    {
        return [
            'primary' => 'Primary',
            'success' => 'Success',
            'warning' => 'Warning',
            'danger' => 'Danger',
            'info' => 'Info',
            'gray' => 'Gray',
        ];
    }

    /**
     * Create an already-sent system notification record for admin audit trail.
     *
     * @param  array<int>|int  $recipientIds
     */
    public static function createFromSystem(
        string $title,
        string $body,
        string $notificationType,
        string $icon,
        string $iconColor,
        ?string $url,
        array|int $recipientIds,
        string $deliveryMethod = 'both',
    ): static {
        $record = static::create([
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'icon_color' => $iconColor,
            'notification_type' => $notificationType,
            'url' => $url,
            'delivery_method' => $deliveryMethod,
            'created_by' => null,
            'source' => 'system',
            'sent_at' => now(),
        ]);

        $ids = is_array($recipientIds) ? $recipientIds : [$recipientIds];
        $pivotData = array_fill_keys(
            $ids,
            ['read_at' => null, 'created_at' => now(), 'updated_at' => now()],
        );
        $record->recipients()->attach($pivotData);

        return $record;
    }

    /**
     * Get notification type options
     */
    public static function getNotificationTypeOptions(): array
    {
        return [
            'info' => 'Info',
            'success' => 'Success',
            'warning' => 'Warning',
            'danger' => 'Danger',
        ];
    }
}
