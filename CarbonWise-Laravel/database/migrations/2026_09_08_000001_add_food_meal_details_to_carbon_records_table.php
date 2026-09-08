<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->table('carbon_records', function (Blueprint $table) {
            $table->string('food_meal_period', 50)->nullable()->after('food_item');
            $table->timestamp('food_consumed_at')->nullable()->after('food_meal_period');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('carbon_records', function (Blueprint $table) {
            $table->dropColumn(['food_meal_period', 'food_consumed_at']);
        });
    }
};
