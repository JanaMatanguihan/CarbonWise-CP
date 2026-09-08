<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->table('carbon_records', function (Blueprint $table) {
            $table->text('transport_item')->nullable()->after('transportation');
            $table->text('office_item')->nullable()->after('electricity');
            $table->text('food_item')->nullable()->after('food');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('carbon_records', function (Blueprint $table) {
            $table->dropColumn(['transport_item', 'office_item', 'food_item']);
        });
    }
};
