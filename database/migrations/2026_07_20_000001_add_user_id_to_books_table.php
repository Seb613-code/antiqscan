<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table): void {
            // Existing private records may predate user accounts. They remain unassigned
            // until an administrator explicitly transfers them to their legitimate owner.
            $table->foreignId('user_id')->after('id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
