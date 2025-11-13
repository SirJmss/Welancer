<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// NEW MODEL NAME: KpiRecord to align with Chapter 1 terminology.
class KpiRecord extends Model
{
    use HasFactory;
    
    // Explicitly set the table name to match the migration change
    protected $table = 'kpi_records';

    protected $fillable = [
        'task_id',
        'rater_id',
        'quality_score',
        'discipline_score',
        'comments',
    ];

    /**
     * The Employee (Manager/Admin) who provided the KPI Record/feedback.
     */
    public function rater(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'rater_id');
    }

    /**
     * The Task this KPI record belongs to.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}