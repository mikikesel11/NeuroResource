<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Accessibility preferences. Belongs to a user when logged in, else keyed
        // by an anonymous cookie id. Mirrors the client localStorage. See §3.5.
        Schema::create('preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('cookie_id')->nullable()->index();
            $table->string('theme')->nullable();             // null => follow OS
            $table->decimal('text_scale', 3, 2)->default(1.00);
            $table->decimal('line_height', 3, 2)->default(1.60);
            $table->string('font')->nullable();
            $table->boolean('reduce_motion')->nullable();    // null => follow OS
            $table->string('locale')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferences');
    }
};
