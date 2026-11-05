<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reading_plans', function (Blueprint $table) {
            $table->string('status', 11)
                ->default('in_progress')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reading_plans', function (Blueprint $table) {
            $table->string('status', 255)
                ->default('in_progress')
                ->change();
        });
    }
};
