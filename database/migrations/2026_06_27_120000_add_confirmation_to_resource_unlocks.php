<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Double opt-in: an unlock starts pending (token set, confirmed_at null)
        // and becomes active once the recipient confirms via the emailed link.
        Schema::table('resource_unlocks', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('email');
            $table->timestamp('confirmed_at')->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('resource_unlocks', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn(['token', 'confirmed_at']);
        });
    }
};
