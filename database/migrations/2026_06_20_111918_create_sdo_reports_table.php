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
    Schema::create('sdo_reports', function (Blueprint $table) {
        $table->id();

        // Report information
        $table->string('title');
        $table->text('description')->nullable();

        // Reporting period
        $table->date('report_date');

        // Report status
        $table->enum('status', [
            'draft',
            'pending',
            'approved',
            'published'
        ])->default('draft');

        // Optional uploaded file
        $table->string('file_path')->nullable();

        // Report statistics
        $table->decimal('total_emissions', 12, 2)->default(0);
        $table->integer('total_users')->default(0);

        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sdo_reports');
    }
};
