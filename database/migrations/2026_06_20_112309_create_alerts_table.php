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
    Schema::create('alerts', function (Blueprint $table) {
        $table->id();

        // Alert can optionally belong to a user
        $table->foreignId('user_id')
              ->nullable()
              ->constrained()
              ->nullOnDelete();

        // Alert content
        $table->string('title');
        $table->text('message');

        // Severity level
        $table->enum('severity', [
            'info',
            'warning',
            'critical'
        ])->default('info');

        // Read status
        $table->boolean('is_read')->default(false);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
