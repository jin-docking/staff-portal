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
    public function index(Request $request)
    {
        // Get request parameters for year, month, and date
        $year = $request->input('year');
        $month = $request->input('month');
        $date = $request->input('date');
    
        // Start query with base condition
        $query = Leave::query();
    
        // Apply filters based on provided parameters
        if ($year) {
            $query->whereYear('start_date', $year);
        }
        if ($month) {
            $query->whereMonth('start_date', $month);
        }
        if ($date) {
            $query->whereDate('start_date', $date);
        }

        // Order the results with pending leaves first
        $query->orderByRaw("CASE WHEN approval_status = 'pending' THEN 0 ELSE 1 END");
        
        // Get filtered leaves
        $leaves = $query->get();
    
        // Eager load users to avoid N+1 queries
        $userIds = $leaves->pluck('user_id')->unique()->toArray();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
    
        foreach ($leaves as $leave) {
            // Retrieve user associated with the leave
            $user = $users->get($leave->user_id);
            if ($user) {
                $leave->first_name = $user->first_name;
                $leave->last_name = $user->last_name;
                $leave->role = $user->role;
            }
    
            // Retrieve creator of the leave
            $creator = $users->get($leave->created_by);
            if ($creator) {
                $leave->creator_name = ($leave->user_id == $creator->id) ? 'Self' : $creator->first_name . ' ' . $creator->last_name;
            }
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
        
        $creator = User::find($leave->created_by);
        $leave->creator_name = ($leave->user_id == $creator->id) ? 'Self' : $creator->first_name . ' ' . $creator->last_name;

        return response()->json($leave);
    
    }        

     /**
     * Approve or reject a leave request.
     */
    public function update(Request $request, $id)
    {
        $admin = Auth::user();

        // if ($admin->role->title != 'Admin') {
        //     return response()->json(['error' => 'You do not have permission to update leave status.'], 403);
        // }

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

        // if ($admin->role->title != 'Admin') {
        //     return response()->json(['error' => 'You do not have permission to delete leave requests.'], 403);
        // }

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
                        
        ]);
        
        $leave = Leave::create([
            'user_id' => $user->id,
            'created_by' => $admin->id,
            'title' => $request->title,
            'category' => $request->category,
            'approval_status' => $request->input('approval_status', 'pending'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,                

        ]);       

        return response()->json([
            'message' => 'User leave created successfullydd',
            'data' => [
                'leave' => $leave,
                'creator_name' => $admin->first_name,
                'user_name' => $user->first_name,
            ],
        ]);
    }


}
