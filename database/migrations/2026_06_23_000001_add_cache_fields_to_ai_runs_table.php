<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_runs', function (Blueprint $table) {
            $table->string('image_hash', 64)->nullable()->after('model')->index();
            $table->string('cache_key', 64)->nullable()->after('image_hash')->index();
            $table->foreignId('cached_from_ai_run_id')->nullable()->after('cache_key')->constrained('ai_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cached_from_ai_run_id');
            $table->dropColumn(['image_hash', 'cache_key']);
        });
    }
};
