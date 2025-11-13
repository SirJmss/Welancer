<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the kpi_records table to store task ratings/KPI data.
     */
    public function up(): void
    {
        Schema::create('kpi_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->unique()->constrained('tasks')->onDelete('cascade');
            $table->foreignId('rater_id')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_records');
    }
};