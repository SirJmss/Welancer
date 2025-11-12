<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as Roles;

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('Roles/Index', [
            'roles' => Roles::with('permissions')->get()->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'permissions' => $r->permissions->pluck('name')->toArray(),
            ])
        ]);
    }

    public function create()
    {
        return Inertia::render('Roles/Create', [
            'permissions' => Permission::pluck('name')->toArray()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Roles::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')
                         ->with('success', 'Role created successfully.');
    }

    // ✅ NEW METHOD ADDED — show single role
public function show(string $id)
{
    $role = Roles::with('permissions')->findOrFail($id);

    return Inertia::render('Roles/Show', [
        'role' => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ],
    ]);
}


    // FIXED: Send clean data → no full model → no blank screen
    public function edit(string $id)
    {
        $role = Roles::with('permissions')->findOrFail($id);

        return Inertia::render('Roles/Edit', [
            'role' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ],
            'allPermissions' => Permission::pluck('name')->toArray(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $role = Roles::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')
                         ->with('success', 'Role updated successfully.');
    }

    public function destroy(string $id)
    {
        $role = Roles::findOrFail($id);
        $role->delete();

        return redirect()->route('roles.index')
                         ->with('success', 'Role deleted successfully.');
    }
}