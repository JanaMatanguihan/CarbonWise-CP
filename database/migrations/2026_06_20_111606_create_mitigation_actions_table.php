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
    Schema::create('mitigation_actions', function (Blueprint $table) {
        $table->id();

        // User who performed or received the mitigation action
        $table->foreignId('user_id')->constrained()->onDelete('cascade');

        // Name of the mitigation strategy
        $table->string('title');

        // Optional description
        $table->text('description')->nullable();

        // Carbon reduction achieved (kg CO₂e)
        $table->decimal('carbon_reduced', 10, 2)->default(0);

        // Status of the action
        $table->enum('status', ['pending', 'in_progress', 'completed'])
              ->default('pending');

        // Date completed (optional)
        $table->date('completed_at')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mitigation_actions');
    }
};
