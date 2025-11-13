<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // <-- NEW IMPORT

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects.
     * Shows all projects, eager-loading the creator and team (users).
     */
    public function index()
    {
        // Fetch all projects, eager-loading the creator and team users
        $projects = Project::with(['creator:id,name,role', 'users:id,name'])
            ->orderBy('due_date', 'asc')
            ->paginate(10);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'canCreate' => Auth::user()->role === 'admin', 
        ]);
    }

    /**
     * Show the form for creating a new project.
     * Updated to pass all available users for team assignment.
     */
    public function create()
    {
        // RBAC Check: Only Admins can access the creation form (Project Proposal)
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('projects.index')->with('error', 'Unauthorized access to project creation.');
        }

        // Fetch all users eligible for project assignment (Managers and Employees)
        $allUsers = User::select('id', 'name', 'role')->whereIn('role', ['manager', 'employee'])->get();

        return Inertia::render('Projects/Create', [
            'allUsers' => $allUsers, // Pass users for team assignment dropdowns
        ]);
    }

    /**
     * Store a newly created project in storage.
     * NEW: Includes logic to sync the assigned team to the project_user pivot table.
     */
    public function store(Request $request)
    {
        // RBAC Check
        if (Auth::user()->role !== 'admin') {
            return back()->with('error', 'Only Administrators can initiate new projects.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            // NEW: Validation for team assignment data structure
            'team' => 'nullable|array',
            'team.*.id' => 'required|exists:users,id',
            'team.*.project_role' => ['required', Rule::in(['manager', 'team_member'])],
        ]);

        // 1. Create the Project
        $project = Project::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'start_date' => $validated['start_date'],
            'due_date' => $validated['due_date'],
            'creator_id' => Auth::id(), // Assign the current user as the creator
            'status' => 'pending', // Projects start as pending
        ]);

        // 2. RESOURCE ALLOCATION: Attach Users to the Project with their specific project_role
        if (!empty($validated['team'])) {
            $syncData = [];
            foreach ($validated['team'] as $member) {
                // Format: [user_id => ['pivot_field' => value]]
                $syncData[$member['id']] = ['project_role' => $member['project_role']];
            }
            $project->users()->sync($syncData);
        }

        return redirect()->route('projects.index')->with('success', 'Project proposal initiated and team assigned successfully.');
    }

    /**
     * Display the specified project.
     * Updated to load the assigned team (users).
     */
    public function show(Project $project)
    {
        // Load the project with its tasks, assigned users (team), and the creator.
        $project->load([
            'tasks' => fn ($query) => $query->orderBy('due_date'),
            'tasks.assignee:id,name',
            'tasks.assigner:id,name',
            'creator:id,name',
            'users:id,name,role' // NEW: Load the assigned team members with pivot data
        ]);

        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    /**
     * Show the form for editing the specified project.
     * Updated to pass all users and the current assigned team.
     */
    public function edit(Project $project)
    {
        // Authorization: Only the creator or a general Admin can edit.
        if (Auth::user()->role !== 'admin' && Auth::id() !== $project->creator_id) {
            return redirect()->route('projects.index')->with('error', 'You are not authorized to edit this project.');
        }
        
        // Eager load the currently assigned users with their pivot data
        $project->load('users:id,name,role');
        // Fetch all users for potential new assignments
        $allUsers = User::select('id', 'name', 'role')->whereIn('role', ['manager', 'employee'])->get();

        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'allUsers' => $allUsers,
        ]);
    }

    /**
     * Update the specified project in storage.
     * NEW: Includes logic to sync the updated team allocation.
     */
    public function update(Request $request, Project $project)
    {
        // Authorization Check
        if (Auth::user()->role !== 'admin' && Auth::id() !== $project->creator_id) {
            return back()->with('error', 'You are not authorized to update this project.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            // NEW: Validation for team assignment data structure
            'team' => 'nullable|array',
            'team.*.id' => 'required|exists:users,id',
            'team.*.project_role' => ['required', Rule::in(['manager', 'team_member'])],
        ]);

        // 1. Update project details
        $project->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'start_date' => $validated['start_date'],
            'due_date' => $validated['due_date'],
            'status' => $validated['status'],
        ]);
        
        // 2. RESOURCE ALLOCATION: Sync Users to the Project
        $syncData = [];
        if (!empty($validated['team'])) {
            foreach ($validated['team'] as $member) {
                $syncData[$member['id']] = ['project_role' => $member['project_role']];
            }
        }
        // Sync replaces the old list with the new list.
        $project->users()->sync($syncData);

        return redirect()->route('projects.show', $project)->with('success', 'Project and team allocation updated successfully.');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        // RBAC Check: Only Admins can permanently delete projects.
        if (Auth::user()->role !== 'admin') {
            return back()->with('error', 'Only Administrators have permission to delete a project.');
        }
        
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project successfully deleted.');
    }
}