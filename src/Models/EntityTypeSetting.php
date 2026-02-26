<?php

declare(strict_types=1);

namespace Zynqa\FilamentNotifications\Models;

use Illuminate\Database\Eloquent\Model;

class EntityTypeSetting extends Model
{
    protected $fillable = [
        'entity_type',
        'label',
        'email_template',
    ];

    public static function getTemplateFor(string $entityType): ?string
    {
        return static::where('entity_type', $entityType)->value('email_template');
    }

    public static function getOrCreateForType(string $type, string $label): static
    {
        return static::firstOrCreate(
            ['entity_type' => $type],
            ['label' => $label]
        );
    }
}
