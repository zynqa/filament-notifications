<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Notification type alias (e.g. the subscribable type 'workitem').
            $table->string('notification_type');
            // off | database | email | both — stored as a string to allow 'off' and
            // remain forward-compatible as new channels are added.
            $table->string('channel')->default('database');
            $table->timestamps();

            $table->unique(['user_id', 'notification_type'], 'notification_preferences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
