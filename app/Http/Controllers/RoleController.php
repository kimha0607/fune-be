<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::select('id', 'name')->get();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);
    
        $role = Role::create($validated);
    
        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role,
        ]);
    }
}
