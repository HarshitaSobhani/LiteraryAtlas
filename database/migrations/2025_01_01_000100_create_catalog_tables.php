<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->smallInteger('first_publish_year')->nullable();
            $table->string('genre', 60)->nullable()->index();
            $table->string('open_library_key', 40)->nullable()->unique();
            $table->unsignedBigInteger('cover_id')->nullable();
            $table->timestamps();

            $table->index('title');
            $table->index('first_publish_year');
        });

        Schema::create('author_book', function (Blueprint $table) {
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->primary(['author_id', 'book_id']);
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 20)->index();
            $table->string('country', 80)->index();
            $table->string('city', 80)->index();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->boolean('coordinates_verified')->default(false);
            $table->string('summary', 300);
            $table->text('significance');
            $table->string('source_url')->nullable();
            $table->timestamps();
        });

        Schema::create('book_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('relation', 30);
            $table->string('certainty', 12)->default('verified');
            $table->string('note', 300)->nullable();
            $table->unique(['book_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_location');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('author_book');
        Schema::dropIfExists('books');
        Schema::dropIfExists('authors');
    }
};
