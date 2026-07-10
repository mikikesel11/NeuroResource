<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_login_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('awarded_date');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'awarded_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_login_bonuses');
    }
};
