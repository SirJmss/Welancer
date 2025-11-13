<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Renames the task_ratings table to kpi_records to align with Chapter 1 terminology.
     * Note: We use the 'employees' table here because the refactor migration already renamed 'users' to 'employees'.
     */
    public function up(): void
    {
        // 1. Drop existing foreign keys before renaming the table
        Schema::table('task_ratings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_id');
            $table->dropConstrainedForeignId('rater_id');
        });

        // 2. Rename the table
        Schema::rename('task_ratings', 'kpi_records');
        
        // 3. Restore foreign keys on the new table name
        Schema::table('kpi_records', function (Blueprint $table) {
            // Restore FK to tasks
            $table->foreignId('task_id')->unique()->constrained('tasks')->onDelete('cascade')->change();
            
            // Restore FK to employees (the rater_id now points to employees)
            $table->foreignId('rater_id')->constrained('employees')->onDelete('restrict')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop existing foreign keys before renaming the table back
        Schema::table('kpi_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_id');
            $table->dropConstrainedForeignId('rater_id');
        });
        
        // 2. Rename the table back
        Schema::rename('kpi_records', 'task_ratings');

        // 3. Restore foreign keys on the old table name (referencing users as defined in the old migration)
        Schema::table('task_ratings', function (Blueprint $table) {
            $table->foreignId('task_id')->unique()->constrained('tasks')->onDelete('cascade')->change();
            // Note: If you run migrations in order, the 'rater_id' field will already exist, 
            // but we must reference the correct table name as it was pre-refactor or post-refactor.
            // Since this migration runs after the employee refactor, it should point to 'employees'.
            // However, to fully reverse, we'll assume a point before the full refactor chain started,
            // or just ensure consistency with the current state. We'll reference 'employees' to keep the chain valid.
            $table->foreignId('rater_id')->constrained('employees')->onDelete('restrict')->change();
        });
    }
};