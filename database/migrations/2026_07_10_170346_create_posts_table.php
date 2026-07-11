<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('external_id');
            $table->string('title');
            $table->string('url', 2048);
            $table->string('author')->nullable();
            $table->string('thumbnail_url', 2048)->nullable();
            $table->json('tags')->nullable();
            $table->json('raw_engagement')->nullable();
            $table->unsignedInteger('trending_score')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('fetched_at');
            $table->foreignId('story_group_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index('trending_score');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
