<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            
            // Link to the Project
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            
            $table->string('title', 255);
            $table->text('description')->nullable();

            // Who the task is assigned to (Employee) [cite: 79]
            $table->foreignId('assigned_to_id')->constrained('users')->onDelete('restrict');
            
            // Who assigned the task (Manager/Leader) [cite: 37]
            $table->foreignId('assigned_by_id')->constrained('users')->onDelete('restrict');
            
            // Status for workflow tracking [cite: 82]
            $table->enum('status', ['to_do', 'doing', 'review', 'done', 'blocked'])->default('to_do');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            
            $table->timestamp('due_date');
            $table->timestamp('completed_at')->nullable(); // Used for performance metrics (KPIs) [cite: 55]

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};