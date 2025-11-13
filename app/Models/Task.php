<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Task extends Model
{
    use HasFactory;
    
    // Mass-assignable fields from the migration
    protected $fillable = [
        'project_id', 
        'title', 
        'description', 
        'assigned_to_id', 
        'assigned_by_id', 
        'status', 
        'priority',
        'due_date',
        'completed_at'
    ];
    
    // Relationship: The Project this Task belongs to
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Relationship: The User the Task is assigned to (Employee)
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    // Relationship: The User who assigned the Task (Manager/Leader)
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }
    
    /**
     * NEW: The rating/feedback associated with this task (one-to-one).
     */
    public function rating(): HasOne
    {
        return $this->hasOne(TaskRating::class);
    }
}