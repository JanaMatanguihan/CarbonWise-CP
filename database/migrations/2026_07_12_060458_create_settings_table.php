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
        Schema::create('settings', function (Blueprint $table) {

            $table->id();

            $table->string('system_name');

            $table->string('organization');

            $table->string('email');

            $table->string('phone')->nullable();

            $table->string('timezone')->default('Asia/Manila');

            $table->string('date_format')->default('F d, Y');

            $table->string('language')->default('English');

            $table->string('theme')->default('light');

            $table->string('accent_color')->default('#15803d');

            $table->integer('session_timeout')->default(60);

            $table->integer('remember_days')->default(7);

            $table->string('default_dashboard')->default('overview');

            $table->integer('records_per_page')->default(10);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};