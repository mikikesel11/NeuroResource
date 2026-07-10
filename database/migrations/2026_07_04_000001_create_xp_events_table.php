<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // 160 covers the worst case "card:{deck}-{name}" (deck <= 64, name <= 80,
            // see cards migration) with headroom, while staying under MySQL's safe
            // indexed-varchar width for utf8mb4.
            $table->string('source', 160);
            $table->unsignedSmallInteger('amount')->default(10);
            $table->timestamp('awarded_at')->useCurrent();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'awarded_at']);
            $table->index(['user_id', 'source', 'awarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_events');
    }
};
