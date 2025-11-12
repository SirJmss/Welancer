<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = Permission::get();
        return Inertia::render('permissions/index',[
            'permissions'=> $permissions
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('permissions/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string'
        
        ]);

        $permission = Permission::create([
            'name' => $request->name
        ]);

        if($permission){
            return redirect()->route('permissions.index')->with('success', 'Permission successfully created!');
        }
        return redirect()->back()->with('error', 'Permission creation failed.');

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $permissions = Permission::findOrFail($id);
        return Inertia::render('permissions/edit',[
            'permissions'=> $permissions
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $permission  = Permission::findOrFail($id);
         
        if($permission){
            $request->validate([
            'name' => 'required|string'
        
        ]);

        $permission->update([
            'name' => $request->name
        ]);
            return redirect()->route('permissions.index')->with('success', 'Permission successfully created!');
        }
        return redirect()->back()->with('error', 'Permission creation failed.');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
     Permission::destroy($id);
    return to_route('permissions.index');

    }
}
