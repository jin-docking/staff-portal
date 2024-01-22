<?php

namespace App\Http\Controllers;

use App\Models\leave;
use Illuminate\Http\Request;
use App\Http\Requests\StoreleaveRequest;
use App\Http\Requests\UpdateleaveRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;


class LeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Add the following code to the controller file       
        $leaves = Leave::all();

        return response()->json($leaves);
    }   

   

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'required|string',
        ]);

      

        $leave =Leave::create([
            'user_id' => $user->id,
            'approval_status' => 'pending',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
        ]);

        // $user->leaves()->save($leave);
        
        return response()->json(['message' => 'Leave applied successfully', 'data' => $leave]);
        
    }

    /**
     * Display the specified resource.
     */
    public function show(leave $leave)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(leave $leave)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, leave $leave)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(leave $leave)
    {
        //
    }
}

