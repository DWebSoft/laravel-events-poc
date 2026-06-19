<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes that keep the event feed's cost independent of the 1.25M row count.
     *
     * - created_time: default "newest first" ordering and date-range filtering.
     * - (status, created_time): the common "published, newest first" view uses a
     *   single index for both the filter and the sort.
     * - (latitude, longitude): bounding-box ("near a city") location filtering.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index('created_time');
            $table->index(['status', 'created_time']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['created_time']);
            $table->dropIndex(['status', 'created_time']);
            $table->dropIndex(['latitude', 'longitude']);
        });
    }
};
