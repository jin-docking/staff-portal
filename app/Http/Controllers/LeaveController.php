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
    public function show($user_id)
    {
       
        $user = Auth::user();

        if ($user->id != $user_id) {
            return response()->json(['error' => 'You do not have permission to view leaves for this user.'], 403);
        }
    
        $leaves = Leave::where('user_id', $user_id)->get();
    
        if ($leaves->isNotEmpty()) {
            return response()->json($leaves);
        } else {
            return response()->json(['message' => 'No leaves found for the specified user'], 404);
        }
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
     public function update(Request $request, $id)
     {        
         $leave = Leave::find($id);
         if (!$leave) {
             return response()->json(['error' => 'Leave not found'], 404);
         }
         $user = Auth::user();
         if ($user->id !== $leave->user_id) {
             return response()->json(['error' => 'You do not have permission to update this leave status.'], 403);
         }             
         $request->validate([
            'approval_status' => 'in:approved,rejected',
             'start_date' => 'date',
             'end_date' => 'date|after_or_equal:start_date',
             'description' => 'string',
         ]);     
         
        //  update the leave
         $leave->update([
            'approval_status' => $request->input('approval_status', $leave->approval_status),
             'start_date' => $request->input('start_date', $leave->start_date),
             'end_date' => $request->input('end_date', $leave->end_date),
             'description' => $request->input('description', $leave->description),
         ]);     
        return response()->json(['message' => 'Leave updated successfully', 'data' => $leave]);
     }   



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
    
        $leave = Leave::findOrFail($id);    

        if ($user->id !== $leave->user_id) {
            return response()->json(['error' => 'You do not have permission to delete this leave.'], 403);
        }    
         //  delete the leave
        $leave->delete();
    
        return response()->json(['message' => 'Leave deleted successfully']);
    }
    
}

