<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('field_key')->index();
            $table->string('label');
            $table->text('value')->nullable();
            $table->string('origin')->default('manual')->index();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->boolean('is_validated')->default(false);
            $table->boolean('is_editable')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['book_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_fields');
    }
};
