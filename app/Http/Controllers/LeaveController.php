<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\LeaveNotificationMail;
use App\Models\CompanyInfo;

class LeaveController extends Controller
{
      /**
     * Display all leaves
     */
    public function index(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();
        //return response()->json($user);
        // Get year and status from query parameters
        $year = $request->query('year');
        $status = $request->query('status');
        $month = $request->input('month');
        $day = $request->input('day');
        $category = $request->input('category');
    
        // Initialize the query
        $query = Leave::where('user_id', $user->id);
    
        // Apply year filter if provided
        if ($year) {
            $query->whereYear('created_at', $year);
        }
    
        // Apply status filter if provided
        if ($status) {
            $query->where('approval_status', $status);
        }

        if ($month) {
            $query->whereMonth('start_date', $month);
        }

        if ($day) {
            $dayDate = Carbon::createFromDate($year, $month, $day);
            $query->whereDate('start_date', $dayDate);
        }
        if ($category) {
            $query->where('category', $category);
        }
    
        // Get the filtered leaves
        $query->orderByRaw("FIELD(approval_status, 'pending', 'approved', 'rejected'), created_at DESC");
        
        $leaves = $query->get();

        // Check whether the leaves are empty
        if ($leaves->isNotEmpty()) {
    
            // Get creators name
            foreach ($leaves as $leave) {
                $creatorName = $leave->user_id === $leave->created_by ? 'Self' : User::where('id', $leave->created_by)->value('first_name').' '.User::where('id', $leave->created_by)->value('last_name');
                $leave->creator_name = $creatorName;
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
        // Find leave
        $leave = Leave::findOrFail($id);

        if (!$leave) {
            return response()->json(['error' => 'Leave not found'], 404);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Check users access permission
        if ($user->id != $leave->user_id) {
            return response()->json(['error' => 'You do not have permission to update this leave status.'], 403);
        }     

        // Get creators name
        $creator = User::find($leave->created_by);
        $leave->creator_name = $creator ? $creator->first_name . ' ' . $creator->last_name : null;
    
        return response()->json($leave);
    }        

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Validate required parameters
        $request->validate([
            'title' => 'required|string',
            'category' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'complimentary_date' => 'nullable|date',
            'description' => 'required|string',
            'leave_type' => 'required|string',
            'leave_session' => 'nullable|string',
        ]);

        if (strtolower($request->category) == 'restricted holiday') {
            $existingRestrictedLeave = Leave::where('user_id', $user->id)
                ->where('category', 'restricted holiday')
                ->where('approval_status', 'approved')
                ->first();
    
            if ($existingRestrictedLeave) {
                return response()->json([
                    'message' => 'You can only take one restricted holiday.',
                ], 400);
            }
        }
        $leaveCount = 0.0;

        // Create leave
        
        $leave = Leave::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'title' => $request->title,
            'category' => $request->category,
            'approval_status' => 'pending',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'complimentary_date' => $request->complimentary_date,
            'description' => $request->description,
            'leave_count' => $leaveCount,
            'loss_of_pay' => $request->loss_of_pay,
            'leave_type' => $request->leave_type,
            'leave_session' => $request->leave_session,
        ]);

        $role = Role::where('title', 'Admin')->first();
        $admin = null;
        if ($role) {
            $admin = User::where('role_id', $role->id)->first();
        }

        $projectManagers = collect(); // Initialize as an empty collection
        $teams = $user->teams;
        if ($teams) {
            foreach ($teams as $team) {
                if ($team->projectManager) {
                    $projectManagers->push($team->projectManager);
                }
            }
        }

        $ccEmails = [];
        if ($projectManagers->isNotEmpty()) { // Check if the collection is not empty
            foreach ($projectManagers as $manager) {
                $ccEmails[] = $manager->email;
            }
        }

        $companyInfo = CompanyInfo::first();
        if ($companyInfo && $admin) {
            $ccEmails[] = $admin->email;
            Mail::to($companyInfo->email)->send(new LeaveNotificationMail($leave, $user, 'request', $ccEmails));
        }

        return response()->json([
            'message' => 'Leave applied successfully',
            'data' => [
                'leave' => $leave,
                'creator_name' => $user->first_name,
            ],
        ]);
    }

  
    public function showLeave()
    {
        // Get the authenticated user
        $user = Auth::user();
    
        // Get the current date
        $currentDate = now();
        
        // Calculate financial year
        $yearStart = $currentDate->month >= 4 ? $currentDate->startOfYear()->addMonths(3) : $currentDate->subYear()->startOfYear()->addMonths(3);
        $yearEnd = $yearStart->copy()->addYear()->subDay();
        
        // Retrieve leave records for the user within the current financial year
        $leaveRecords = Leave::where('user_id', $user->id)
            ->where('approval_status', 'approved')
            ->whereBetween('start_date', [$yearStart, $yearEnd])
            ->get();

        // Calculate available leave count
        $annualLeave = $user->role->leaves;
        $takenLeaveCount = $leaveRecords->where('category', '!=', 'complementary')
                                        ->where('category', '!=', 'restricted holiday')
                                        ->where('loss_of_pay', '!=', 'yes')->sum('leave_count');
                                        
        $availableLeave = max(0, $annualLeave - $takenLeaveCount);

        // Group leave records by category and calculate total leave for each category
        $leaveByCategory = $leaveRecords->groupBy('category')->map(function ($leaves) {
            return $leaves->sum('leave_count');
        });

        // Split the leave records into biannual periods
        $firstHalfEndDate = $yearStart->copy()->addMonths(6)->subDay();
        $firstHalfRecords = $leaveRecords->filter(function ($record) use ($yearStart, $firstHalfEndDate) {
            return $record->start_date->between($yearStart, $firstHalfEndDate);
        })->values();

        $secondHalfRecords = $leaveRecords->filter(function ($record) use ($firstHalfEndDate) {
            return $record->start_date->gt($firstHalfEndDate);
        })->values();

        // Calculate leave count for each half
        $firstHalfLeaveCount = $firstHalfRecords->sum('leave_count');
        $secondHalfLeaveCount = $secondHalfRecords->sum('leave_count');

        return response()->json([
            'total_leave' => $annualLeave,
            'leave_records' => $leaveRecords,
            'first_half_records' => $firstHalfRecords,
            'first_half_leave_count' => $firstHalfLeaveCount,
            'second_half_records' => $secondHalfRecords,
            'second_half_leave_count' => $secondHalfLeaveCount,
            'available_leave' => $availableLeave,
            'total_leave_by_category' => $leaveByCategory,
        ]);
    }
   
   
    /**
     * Update the specified resource in storage.
     */    
     public function update(Request $request, $id)
     {        
        // Find leaves
         $leave = Leave::findOrFail($id);

         if (!$leave) {
             return response()->json(['error' => 'Leave not found'], 404);
         }

         // Get the authenticated user
         $user = Auth::user();
         if ($user->id != $leave->user_id) {
             return response()->json(['error' => 'You do not have permission to update this leave status.'], 403);
         }         
         
         if ($leave->approval_status == 'approved') {
            return response()->json(['error' => 'This leave connot be updated'], 403);
         }
         
         $request->validate([
            'title' => 'required|string',
            'category' => 'required|string',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'complimentary_date' => 'nullable|date',
            'description' => 'string',
         ]);     
         
        //  update the leave
         $leave->update([
            'title' => $request->input('title', $leave->title),
            'category' => $request->input('category', $leave->category),
            'approval_status' => 'pending',
            'start_date' => $request->input('start_date', $leave->start_date),
            'end_date' => $request->input('end_date', $leave->end_date),
            'complimentary_date' => $request->input('complimentary_date', $leave->complimentary_date),
            'description' => $request->input('description', $leave->description),
            'leave_count' => $request->input('leave_count', $leave->leave_count),
            'loss_of_pay' => $request->input('loss_of_pay', $leave->loss_of_pay),
            'leave_type' => $request->input('leave_type', $leave->leave_type),
            'leave_session' => $request->input('leave_session', $leave->leave_session),
         ]);     

         $role = Role::where('title', 'Admin')->first();
         $admin = null;
         if ($role) {
             $admin = User::where('role_id', $role->id)->first();
         }
     
         $teams = $user->teams;
         $projectManager = [];
     
         if ($teams) {
            foreach ($teams as $team) {
                $projectManager = $team->projectManager;
            }
        }

        $ccEmails = [];
        if ($projectManager) {
            foreach ($projectManager as $manager)
            $ccEmails[] = $manager->email;
        }
         $companyInfo = CompanyInfo::first();
         if ($companyInfo && $admin) {
             $ccEmails[] = $admin->email;
             Mail::to($companyInfo->email)->send(new LeaveNotificationMail($leave, $user, 'request', $ccEmails));
         }

        return response()->json(['message' => 'Leave updated successfully', 'data' => $leave]);
       
     }   

//counts all users taken leave and returns users with highest and lowest leave.
    public function userLeaveCount()
    {
        $users = User::all();

        $currentDate = now();

        //calculates financial year
        $yearStart = $currentDate->copy()->startOfYear();
        if ($currentDate->month >= 4) {
            $yearStart->addMonths(3);
        } else {
            $yearStart->subYear()->addMonths(3);
        }
        //$yearStart = $currentDate->month >= 4 ? $currentDate->startOfYear()->addMonths(3) : $currentDate->subYear()->startOfYear()->addMonths(3);
        $yearEnd = $yearStart->copy()->addYear()->subDay();

        
        $leaves = Leave::select(DB::raw('user_id, COUNT(id) as total_leaves'))
            ->where('category', '!=', 'complementary')
            ->where('approval_status', 'approved')
            ->where('category', '!=', 'restricted holiday')
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

        $leaves = Leave::where('user_id', $user->id)->orderBy('created_at', 'DESC')->limit(3)->get();

        return response()->json(['data' => $leaves], 200);
        
    }

    public function availableLeave($id)
    {
        $user = User::findOrFail($id);

        $takenLeaveCount = Leave::where('user_id', $user->id)->where('category', '!=', 'complementary')
        ->where('category', '!=', 'restricted holiday')
        ->where('loss_of_pay', '!=', 'yes')->sum('leave_count');

        $annualLeave = $user->role->leaves;

        

        $availableLeave = max(0, $annualLeave - $takenLeaveCount);

        $availableLeaveString = (string)  $availableLeave;

        return response()->json(['data' => [
            'total_leaves' => $annualLeave,
            'available_leave' => $availableLeave,
            'available_leave_string' => $availableLeaveString
        ]], 200);
        
    }

}

