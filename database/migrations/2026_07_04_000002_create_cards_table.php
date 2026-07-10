<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            // Bounded so "card:{deck}-{name}" always fits xp_events.source (see that migration).
            $table->string('name', 80);
            $table->text('description');
            $table->string('deck', 64);
            $table->unsignedSmallInteger('xp_earned')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['deck', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
