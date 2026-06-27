<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cross-device save for the Adventure game (play.neuroscouts.org).
        // Anonymous players save in localStorage; this is the opt-in account sync.
        Schema::create('game_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('story_id');
            $table->string('scene_id');
            $table->json('state_json')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'story_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_progress');
    }
};
