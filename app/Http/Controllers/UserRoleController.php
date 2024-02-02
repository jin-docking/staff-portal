<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class UserRoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return response()->json($roles);
    }


    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'leaves' => 'required|integer',
        ]);

        $roles = Role::create([
            'title' => $request->title,
            'description' => $request->description,
            'leaves' => $request->leaves,
        ]);

        return response()->json(['message' => 'Role created successfully', 'data' => $roles]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'leaves' => 'required|integer',
        ]);
        
        $roles = Role::create([
            'title' => $request->input('title', $roles->title),
            'description' => $request->input('description', $roles->description),
            'leaves' => $request->input('leaves', $user->leaves),
        ]);

        return response()->json(['message' => 'role updated'], 200);
    }

    
}
