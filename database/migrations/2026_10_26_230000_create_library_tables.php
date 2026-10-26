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
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('normalized_url', 2048);
            $table->char('normalized_hash', 64);
            $table->string('title', 200);
            $table->text('description');
            $table->string('favicon_url', 2048)->default('');
            $table->string('preview_image_url', 2048)->default('');
            $table->string('category', 100)->default('');
            $table->mediumText('notes');
            $table->json('tags');
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'normalized_hash']);
            $table->unique(['user_id', 'id']);
            $table->index(['user_id', 'is_archived', 'created_at']);
            $table->index(['user_id', 'is_favorite']);
            $table->index(['user_id', 'title']);
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description');
            $table->string('icon', 40)->default('folder');
            $table->string('accent_color', 7)->default('#F97316');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_pinned_to_home')->default(true);
            $table->boolean('is_collapsed')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'id']);
            $table->index(['user_id', 'position']);
        });

        Schema::create('collection_resource', function (Blueprint $table) {
            $table->foreignUuid('user_id');
            $table->foreignUuid('collection_id');
            $table->foreignUuid('resource_id');
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['collection_id', 'resource_id']);
            $table->index(['user_id', 'collection_id', 'position']);
            $table->index(['user_id', 'resource_id']);

            $table->foreign(['user_id', 'collection_id'])
                ->references(['user_id', 'id'])
                ->on('collections')
                ->cascadeOnDelete();
            $table->foreign(['user_id', 'resource_id'])
                ->references(['user_id', 'id'])
                ->on('resources')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_resource');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('resources');
    }
};
