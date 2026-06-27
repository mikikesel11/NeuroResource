<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');          // translatable
            $table->json('summary');        // translatable
            $table->string('type');         // pdf | printable | link
            $table->string('file_path')->nullable();    // object storage key
            $table->string('external_url')->nullable();
            $table->string('access')->default('free');  // free | email
            $table->unsignedBigInteger('download_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['access', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
