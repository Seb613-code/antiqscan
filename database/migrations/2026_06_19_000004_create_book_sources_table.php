<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('source_type')->index();
            $table->string('title')->nullable();
            $table->string('url', 2048)->nullable();
            $table->text('citation')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('user_approved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_sources');
    }
};
