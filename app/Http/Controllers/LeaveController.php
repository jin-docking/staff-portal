<?php

namespace App\Http\Controllers;

use App\Models\Leave;
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
            'title' => 'required|string',
            'category' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'required|string',
        ]);      

        $leave =Leave::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'category' => $request->category,
            'approval_status' => 'pending',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
        ]);

           
        return response()->json(['message' => 'Leave applied successfully', 'data' => $leave]);
        
        
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $user = Auth::user();
    
        $leaves = Leave::where('user_id', $user->id)->get();
    
        if ($leaves->isNotEmpty()) {
            return response()->json($leaves);
        } else {
            return response()->json(['message' => 'No leaves found for the authenticated user'], 404);
        }
    }
    public function showLeave()
    {
        $user = Auth::user();

        
        $leaveRecords = Leave::where('user_id', $user->id)->get();

        $annualLeaveAllowance = 14; 
        $takenLeaveCount = $leaveRecords->count();
        $availableLeave = max(0, $annualLeaveAllowance - $takenLeaveCount);

        $uniqueLeaveCategories = $leaveRecords->pluck('category')->unique()->filter();
        
        $leaveByCategory = [];
        $totalLeaveByCategory = [];

        foreach ($uniqueLeaveCategories as $category) {
            $leaveByCategory[$category] = $leaveRecords->where('category', $category);
            $totalLeaveByCategory[$category] = $leaveByCategory[$category]->count();
        }

        return response()->json([
            'leave_records' => $leaveRecords,
            'available_leave' => $availableLeave,
            'leave_by_category' => $leaveByCategory,
            'total_leave_by_category' => $totalLeaveByCategory,
        ]);

    
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
         if ($user->id != $leave->user_id) {
             return response()->json(['error' => 'You do not have permission to update this leave status.'], 403);
         }             
         
         $request->validate([
            'title' => 'required|string',
            'category' => 'required|string',
            'approval_status' => 'in:approved,rejected',
             'start_date' => 'date',
             'end_date' => 'date|after_or_equal:start_date',
             'description' => 'string',
         ]);     
         
        //  update the leave
         $leave->update([
            'title' => $request->input('title', $leave->title),
            'category' => $request->input('category', $leave->category),
            'approval_status' => $request->input('approval_status', $leave->approval_status),
             'start_date' => $request->input('start_date', $leave->start_date),
             'end_date' => $request->input('end_date', $leave->end_date),
             'description' => $request->input('description', $leave->description),
         ]);     
        return response()->json(['message' => 'Leave updated successfully', 'data' => $leave]);
        /*$leave = Leave::find($id);
            if (!$leave) {
                return response()->json(['error' => 'Leave not found'], 404);
            }
            $user = Auth::user();
            
           return response()->json(['data' => ['user_id' => $user->id,'leave_id' => $leave->id, 'leave_user_id' => $leave->user_id]]);*/
     }   

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
    
        $leave = Leave::findOrFail($id);    

        if ($user->id != $leave->user_id) {
            return response()->json(['error' => 'You do not have permission to delete this leave.'], 403);
        }    
         //  delete the leave
        $leave->delete();
    
        return response()->json(['message' => 'Leave deleted successfully']);
    }
    
}

