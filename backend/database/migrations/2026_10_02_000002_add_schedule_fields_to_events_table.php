<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Capstone 2: events double as the EMO's schedule of every NEU event, so they
 * gain the columns of the office's schedule sheet. The free-text venue moves
 * into venue_details, and venues become a managed list linked to buildings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('department')->nullable()->after('description');
            $table->foreignId('venue_id')->nullable()->after('department')->constrained('venues')->nullOnDelete();
            $table->string('venue_details')->nullable()->after('venue_id');
            $table->date('end_date')->nullable()->after('event_date');
            $table->time('end_time')->nullable()->after('event_time');
            $table->string('control_number')->nullable()->after('budget');
            $table->text('remarks')->nullable()->after('control_number');
            $table->boolean('needs_preparation')->default(false)->after('remarks');
        });

        // Keep any existing free-text venue as the room/details text.
        DB::table('events')->update(['venue_details' => DB::raw('venue')]);

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('venue');
            $table->time('event_time')->nullable()->change();
            $table->enum('status', ['upcoming', 'completed', 'cancelled'])->default('upcoming')->change();
        });

        // Events that already have tasks were being prepared by the EMO.
        DB::table('events')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('tasks')->whereColumn('tasks.event_id', 'events.id'))
            ->update(['needs_preparation' => true]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('venue')->default('');
        });

        DB::table('events')->update(['venue' => DB::raw("COALESCE(venue_details, '')")]);
        DB::table('events')->where('status', 'cancelled')->update(['status' => 'upcoming']);

        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venue_id');
            $table->dropColumn(['department', 'venue_details', 'end_date', 'end_time', 'control_number', 'remarks', 'needs_preparation']);
            $table->enum('status', ['upcoming', 'completed'])->default('upcoming')->change();
        });
    }
};
