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
        Schema::connection('neon')->table('users', function (Blueprint $table) {
            $table->string('sr_code')->nullable()->unique();
            $table->string('campus')->nullable();
            $table->string('year_level')->nullable();
            $table->string('profile_photo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('neon')->table('users', function (Blueprint $table) {
            $table->dropColumn([
                'sr_code',
                'campus',
                'year_level',
                'profile_photo',
            ]);
        });
    }
};