<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            
            $table->string('title', 255);
            $table->text('description')->nullable();
            
            // Creator is typically the CEO/Admin who initiates the project proposal [cite: 75]
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            
            // Status for progress monitoring [cite: 26, 38]
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');

            $table->timestamp('start_date')->nullable();
            $table->timestamp('due_date')->nullable(); // Adherence to deadlines [cite: 65]
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};