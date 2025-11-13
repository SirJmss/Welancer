<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    /**
     * Calculate and display the live leaderboard ranking.
     * Ranks users based on Task Completion, Contribution Quality, and Timeline Discipline.
     */
    public function index()
    {
        // Only target 'employee' users for the leaderboard, as managers/HR/Admins don't compete.
        $leaderboard = User::query()
            ->select('users.id', 'users.name')
            // Join Tasks assigned to the user
            ->join('tasks', 'users.id', '=', 'tasks.assigned_to_id')
            // Join Ratings for completed tasks
            ->leftJoin('task_ratings', 'tasks.id', '=', 'task_ratings.task_id')
            
            // Filter to include only completed tasks that have been rated
            ->where('tasks.status', 'done')
            ->whereNotNull('tasks.completed_at')
            
            // Core Aggregation Logic for the Leaderboard
            ->groupBy('users.id', 'users.name')
            ->selectRaw('
                COUNT(tasks.id) as tasks_completed,
                AVG(task_ratings.quality_score) as avg_quality,
                AVG(task_ratings.discipline_score) as avg_discipline,
                
                -- Calculate a composite rank score (e.g., 50% Quality, 50% Discipline)
                (AVG(task_ratings.quality_score) * 0.5 + AVG(task_ratings.discipline_score) * 0.5) as rank_score
            ')
            ->having('tasks_completed', '>', 0) // Only show users with completed tasks
            
            // Order by the composite score (highest first)
            ->orderByDesc('rank_score')
            ->orderByDesc('tasks_completed')
            ->get();

        return Inertia::render('Leaderboard/Index', [
            'leaderboard' => $leaderboard,
            'reportDate' => now()->toFormattedDateString(),
        ]);
    }
}