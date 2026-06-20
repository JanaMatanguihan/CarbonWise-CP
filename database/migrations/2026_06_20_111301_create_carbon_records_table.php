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
    Schema::create('carbon_records', function (Blueprint $table) {
        $table->id();

        // User who owns this carbon record
        $table->foreignId('user_id')->constrained()->onDelete('cascade');

        // Carbon emission categories (kg CO₂e)
        $table->decimal('transportation', 10, 2)->default(0);
        $table->decimal('electricity', 10, 2)->default(0);
        $table->decimal('food', 10, 2)->default(0);
        $table->decimal('waste', 10, 2)->default(0);

        // Automatically calculated total
        $table->decimal('total_emission', 10, 2)->default(0);

        // Date this record applies to
        $table->date('record_date');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carbon_records');
    }
};
