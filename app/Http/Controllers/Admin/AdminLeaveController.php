<?php

namespace App\Http\Controllers\Admin;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminLeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $admin = Auth::user();    

    if ($admin->role != 'Admin') {
        return response()->json(['error' => 'You do not have permission to view leave requests'], 403);
    }
 
    $pendingLeaves = Leave::where('approval_status', 'pending')->get();

    $allLeaves = Leave::all();

    $leaves = $pendingLeaves->concat($allLeaves->diff($pendingLeaves));

    foreach ($leaves as $leave) {
        $user = User::find($leave->user_id); 
        $leave->first_name = $user->first_name;
        $leave->last_name = $user->last_name; 
        $leave->role = $user->role;

    }

    return response()->json($leaves);
    }

    /**
     * Display a specific leave
     */

    public function show($id)
    {
        $leave = Leave::find($id);
    
        if (!$leave) {
            return response()->json(['error' => 'Leave not found'], 404);
        }
    
        $user = Auth::user();
    
        if ($user->role === 'Admin') {
            return response()->json($leave);
        } else {
            return response()->json(['error' => 'You do not have permission to view this leave.'], 403);
        }
    }        

     /**
     * Approve or reject a leave request.
     */
    public function update(Request $request, $id)
    {
        $admin = Auth::user();

        if ($admin->role != 'Admin') {
            return response()->json(['error' => 'You do not have permission to update leave status.'], 403);
        }

        $request->validate([
            'approval_status' => 'required|in:approved,rejected',
        ]);

        $leave = Leave::find($id);

        if (!$leave) {
            return response()->json(['error' => 'Leave not found'], 404);
        }

        // Update the approval status
        $leave->update([
            'approval_status' => $request->input('approval_status'),
        ]);

        return response()->json(['message' => 'Leave status updated successfully', 'data' => $leave]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $admin = Auth::user();

        if ($admin->role != 'Admin') {
            return response()->json(['error' => 'You do not have permission to delete leave requests.'], 403);
        }

        $leave = Leave::find($id);

        if (!$leave) {
            return response()->json(['error' => 'Leave not found'], 404);
        }
        // Delete the leave
        $leave->delete();

        return response()->json(['message' => 'Leave deleted successfully']);
    } 

    
    public function store(Request $request, $userId)
    {
        $admin = Auth::user();

        if ($admin->role != 'Admin') {
            return response()->json(['error' => 'You do not have permission to create user leave.'], 403);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $request->validate([
            'title' => 'required|string',
            'category' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'required|string',
            'approval_status' => 'in:approved,rejected',
            'first_name' => 'required|string', 
            'role' => 'required|string',  
            
        ]);
        if ($user->first_name != $request->first_name || $user->role != $request->role) {
            return response()->json(['error' => 'Selected user details do not match.'], 422);
        }
        $leave = Leave::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'category' => $request->category,
            'approval_status' => $request->input('approval_status', 'pending'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'first_name' => $request->first_name,
            'role' => $request->role,

        ]);

        return response()->json(['message' => 'User leave created successfully','data' => $leave]);
    }


}
