<?php

namespace App\Http\Controllers;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks assigned to the current user (My Tasks).
     */
    public function index()
    {
        // Fetch tasks assigned to the currently logged-in user
        $myTasks = Task::where('assigned_to_id', Auth::id())
            ->with('project:id,title')
            ->orderBy('due_date', 'asc')
            ->paginate(10);

        return Inertia::render('Tasks/Index', [
            'tasks' => $myTasks,
            // For showing 'Manage Tasks' option
            'isManager' => in_array(Auth::user()->role, ['admin', 'manager']), 
        ]);
    }

    /**
     * Show the form for creating a new task.
     */
    public function create()
    {
        // RBAC Check: Only Managers/Admins can assign tasks
        if (!in_array(Auth::user()->role, ['admin', 'manager'])) {
            return redirect()->route('tasks.index')->with('error', 'Only Managers or Administrators can assign new tasks.');
        }

        // Pass a list of potential employees and open projects for assignment
        $employees = User::where('role', 'employee')->select('id', 'name')->get();
        $openProjects = Project::whereIn('status', ['pending', 'in_progress'])->select('id', 'title')->get();

        return Inertia::render('Tasks/Create', [
            'employees' => $employees,
            'openProjects' => $openProjects,
        ]);
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request)
    {
        // RBAC Check
        if (!in_array(Auth::user()->role, ['admin', 'manager'])) {
            return back()->with('error', 'Unauthorized to assign tasks.');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to_id' => 'required|exists:users,id',
            'due_date' => 'required|date|after:today',
            'priority' => 'required|in:low,medium,high',
        ]);

        Task::create([
            ...$validated,
            'assigned_by_id' => Auth::id(), // The manager/admin who is assigning the task
            'status' => 'to_do',
        ]);

        return redirect()->route('tasks.index')->with('success', 'Task assigned successfully.');
    }
    
    /**
     * Display the specified task.
     */
    public function show(Task $task)
    {
        // Load relationships for detailed view
        $task->load(['project:id,title', 'assignee:id,name,role', 'assigner:id,name,role']);

        return Inertia::render('Tasks/Show', [
            'task' => $task,
        ]);
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task)
    {
        // Authorization: Only the Assignee or the Assigner (Manager/Admin) can access the edit form.
        if (Auth::id() !== $task->assigned_to_id && Auth::id() !== $task->assigned_by_id) {
            return redirect()->route('tasks.index')->with('error', 'You are not authorized to edit this task.');
        }

        $employees = User::where('role', 'employee')->select('id', 'name')->get();
        $openProjects = Project::whereIn('status', ['pending', 'in_progress'])->select('id', 'title')->get();
        
        return Inertia::render('Tasks/Edit', [
            'task' => $task,
            'employees' => $employees,
            'openProjects' => $openProjects,
        ]);
    }

    /**
     * Update the specified task (for status change or general task details).
     */
    public function update(Request $request, Task $task)
    {
        // Authorization: Only the assignee or the manager/admin can update the task
        if (Auth::id() !== $task->assigned_to_id && !in_array(Auth::user()->role, ['admin', 'manager'])) {
            return back()->with('error', 'You are not authorized to update this task.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to_id' => 'required|exists:users,id',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:to_do,doing,review,done,blocked',
        ]);

        $updateData = [
            ...$validated,
            // Automatically set completed_at only when status changes to 'done' and it's not already set
            'completed_at' => ($validated['status'] === 'done' && is_null($task->completed_at)) ? now() : $task->completed_at,
        ];

        $task->update($updateData);

        return back()->with('success', 'Task details and progress updated.');
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task)
    {
        // Authorization: Only the manager/admin who assigned it, or a general admin, can delete a task.
        if (!in_array(Auth::user()->role, ['admin']) && Auth::id() !== $task->assigned_by_id) {
            return back()->with('error', 'You are not authorized to delete this task.');
        }
        
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task successfully deleted.');
    }
}
