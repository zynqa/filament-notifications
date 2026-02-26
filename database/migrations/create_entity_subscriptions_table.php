<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subscribable_type');
            $table->unsignedBigInteger('subscribable_id');
            $table->enum('channel', ['database', 'email', 'both'])->default('database');
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'subscribable_type', 'subscribable_id'], 'entity_subscriptions_unique');
            $table->index(['subscribable_type', 'subscribable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_subscriptions');
    }
};
