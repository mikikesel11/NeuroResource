<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Records an email-gated unlock (lead capture). NOT a payment — paid
        // goods live in Shopify. See docs/system-design.md §3.2.
        Schema::create('resource_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->timestamps();

            $table->index(['resource_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_unlocks');
    }
};
