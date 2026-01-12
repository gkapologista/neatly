<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->time('scheduled_time')->nullable()->after('scheduled_at');
            $table->tinyInteger('day_of_week')->nullable()->after('scheduled_time')->comment('1=Monday, 7=Sunday');
            $table->tinyInteger('day_of_month')->nullable()->after('day_of_week')->comment('1-31');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['scheduled_time', 'day_of_week', 'day_of_month']);
        });
    }
};
