<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;


class LeaveController extends Controller
{
      /**
     * Display all leaves
     */
    public function index()
    {
        $user = Auth::user();
    
        $leaves = Leave::where('user_id', $user->id)->paginate(10);

       
        if ($leaves->isNotEmpty()) {

            foreach ($leaves as $leave) {
                $creator = User::find($leave->created_by);
                $leave->creator_name = $creator ? $creator->first_name . ' ' . $creator->last_name : null;
            }

            return response()->json($leaves);
        } else {
            return response()->json(['message' => 'No leaves found for the authenticated user'], 404);
        }
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
        if ($user->id != $leave->user_id) {
            return response()->json(['error' => 'You do not have permission to update this leave status.'], 403);
        }     
        $creator = User::find($leave->created_by);
        $leave->creator_name = $creator ? $creator->first_name . ' ' . $creator->last_name : null;
    
        return response()->json($leave);
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
            'created_by' => $user->id,                                 
            'title' => $request->title,
            'category' => $request->category,
            'approval_status' => 'pending',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
        ]);
           
        return response()->json(['message' => 'Leave applied successfully',    
            'data' => [
                'leave' => $leave,
                'creator_name' => $user->first_name,
            ],
        ]);
    }
  
    public function showLeave()
    {
        $user = Auth::user();
       
        $currentDate = now();

        $yearStart = $currentDate->month >= 4 ? $currentDate->startOfYear()->addMonths(3) : $currentDate->subYear()->startOfYear()->addMonths(3);
        $yearEnd = $yearStart->copy()->addYear()->subDay();
        
        $leaveRecords = Leave::where('user_id', $user->id)
        ->where('approval_status', 'approved')
        ->whereBetween('start_date', [$yearStart, $yearEnd])
        ->get();

        $annualLeave = $user->role->leaves; 
        $takenLeaveCount = $leaveRecords->count();
        $availableLeave = max(0, $annualLeave - $takenLeaveCount);

        $leaveCategories = $leaveRecords->pluck('category')->unique()->filter();
        
        $leaveByCategory = [];
        $totalLeaveByCategory = [];

        foreach ($leaveCategories as $category) {
            $leaveByCategory[$category] = $leaveRecords->where('category', $category);
            $totalLeaveByCategory[$category] = $leaveByCategory[$category]->count();
        }

        return response()->json([
            'total_leave' => $annualLeave,
            'leave_records' => $leaveRecords,
            'available_leave' => $availableLeave,
            //'leave_by_category' => $leaveByCategory,
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
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'description' => 'string',
         ]);     
         
        //  update the leave
         $leave->update([
            'title' => $request->input('title', $leave->title),
            'category' => $request->input('category', $leave->category),
            'approval_status' => 'pending',
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

//counts all users taken leave and returns users with highest and lowest leave.
    public function userLeaveCount()
    {
        $users = User::all();

        $currentDate = now();

        $yearStart = $currentDate->copy()->startOfYear();
        if ($currentDate->month >= 4) {
            $yearStart->addMonths(3);
        } else {
            $yearStart->subYear()->addMonths(3);
        }
        //$yearStart = $currentDate->month >= 4 ? $currentDate->startOfYear()->addMonths(3) : $currentDate->subYear()->startOfYear()->addMonths(3);
        $yearEnd = $yearStart->copy()->addYear()->subDay();

        $leaves = Leave::select(DB::raw('user_id, COUNT(id) as total_leaves'))
            ->where('approval_status', 'approved')
            ->whereBetween('start_date', [$yearStart, $yearEnd])
            ->groupBy('user_id')
            ->orderBy('total_leaves')
            ->get();

        if(empty($leaves)){
            return response()->json(['message' => 'leaves is empty']);
        }

        $highestLeaves = $leaves->last();
        $lowestLeaves = $leaves->first();

        $highestUser = User::findOrFail($highestLeaves->user_id);
        $lowestUser = User::findOrFail($lowestLeaves->user_id);

        return response()->json([
            'Highest_leave' => [
                'user' => $highestUser->first_name .' '.$highestUser->last_name,
                'email' => $highestUser->email,
                'total_leaves' => $highestLeaves->total_leaves
            ],
            'Lowest_leave' => [
                'user' => $lowestUser->first_name .' '.$lowestUser->last_name,
                'email' => $lowestLeaves->email,
                'total_leaves' => $lowestLeaves->total_leaves
            ]
        ]);
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

        if ($leave->approval_status == 'approved'){
            return response()->json(['error' => 'You do not have permission to delete this leave.'], 403);
        }
        
         //  delete the leave
        $leave->delete();
    
        return response()->json(['message' => 'Leave deleted successfully']);
    }
    
    public function recentLeaveRequests()
    {
        $user = Auth::user();

        $leaves = Leave::where('user_id', $user->id)->orderBy('created_at', 'DESC')->get();

        $recentLeave = $leaves->first();

        return response()->json(['data' => $recentLeave], 200);
        
    }
}

