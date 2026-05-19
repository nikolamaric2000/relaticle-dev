<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offerings', function (Blueprint $table): void {
            $table->foreignUlid('creator_id')->nullable()->after('type')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('offerings', function (Blueprint $table): void {
            $table->dropForeign(['creator_id']);
            $table->dropColumn('creator_id');
        });
    }
};