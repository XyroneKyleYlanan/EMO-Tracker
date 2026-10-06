<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Internal: an NEU event. External: organized by an outside person or group.
            $table->enum('event_type', ['internal', 'external'])->default('internal')->after('department');
            // Where a rescheduled event was first scheduled; empty if it was never moved.
            $table->date('original_date')->nullable()->after('end_time');
            $table->time('original_time')->nullable()->after('original_date');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['event_type', 'original_date', 'original_time']);
        });
    }
};
