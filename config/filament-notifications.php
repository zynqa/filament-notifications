<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Navigation Settings
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'group' => 'Users & Roles',
        'sort' => 3,
        'icon' => 'heroicon-o-bell-alert',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Settings
    |--------------------------------------------------------------------------
    | Uses Filament Shield permissions if available
    */
    'permissions' => [
        'enabled' => true,
        'prefix' => 'admin::notification',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Icon Options
    |--------------------------------------------------------------------------
    | Predefined heroicons for notification icon selection
    */
    'default_icons' => [
        'heroicon-o-bell' => 'Bell',
        'heroicon-o-bell-alert' => 'Bell Alert',
        'heroicon-o-exclamation-triangle' => 'Exclamation Triangle',
        'heroicon-o-exclamation-circle' => 'Exclamation Circle',
        'heroicon-o-information-circle' => 'Information Circle',
        'heroicon-o-check-circle' => 'Check Circle',
        'heroicon-o-x-circle' => 'X Circle',
        'heroicon-o-shield-exclamation' => 'Shield Exclamation',
        'heroicon-o-megaphone' => 'Megaphone',
        'heroicon-o-chat-bubble-left-right' => 'Chat Bubble',
        'heroicon-o-document-text' => 'Document Text',
        'heroicon-o-light-bulb' => 'Light Bulb',
        'heroicon-o-sparkles' => 'Sparkles',
    ],
];
