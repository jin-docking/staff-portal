<?php

namespace App\Http\Controllers\Admin;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LeaveController;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class AdminLeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get request parameters for year, month, day, user Id and status
        $year = $request->input('year');
        $month = $request->input('month');
        $day = $request->input('day');
        $userId = $request->input('user_id');
        $status = $request->input('status');

        //// Start query with base condition and eager load users
        $query = Leave::with('user');

        if ($year) {
            // Set financial year dates
            $financialYearStart = Carbon::createFromDate($year, 4, 1);
            $financialYearEnd = Carbon::createFromDate($year + 1, 3, 31);
            //Log::info('Financial Year Start: ' . $financialYearStart->toDateString());
            // Filter by financial year
            $query->whereBetween('start_date', [$financialYearStart, $financialYearEnd]);
        }
        if ($month) {
            $query->whereMonth('start_date', $month);
        }
        if ($day) {
            $dayDate = Carbon::createFromDate($year, $month, $day);
            $query->whereDate('start_date', $dayDate);
        }
        if ($status) {
            $query->where('approval_status', $status);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Order the results with pending leaves first
        $query->orderBy('approval_status', 'asc');

        $leaves = $query->get();

        $leaveReport = [];

        if ($userId) {

            // Filter leave records by financial year
            $leaveRecordsQuery = Leave::where('user_id', $userId)
                ->where('approval_status', 'approved')
                ->whereBetween('start_date', [$financialYearStart, $financialYearEnd]);

            if ($month) {
                $leaveRecordsQuery->whereMonth('start_date', $month);
            }

            $leaveRecords = $leaveRecordsQuery->get();
            
            $user = User::find($userId);
            $annualLeave = $user->role->leaves;
            $takenLeaveCount = $leaveRecords->where('category', '!=', 'complimentary')->count();
            $availableLeave = max(0, $annualLeave - $takenLeaveCount);

            //Log::info('Leave Records Count: ' . $leaveRecords->count());

            $leaveByCategory = $leaveRecords->groupBy('category')->map->count();

            $firstHalfRecords = $leaveRecords->filter(function ($record) use ($financialYearStart) {
                return $record->start_date->lte($financialYearStart->copy()->addMonths(5));
            })->values();
    
            $secondHalfRecords = $leaveRecords->filter(function ($record) use ($financialYearStart) {
                return $record->start_date->gt($financialYearStart->copy()->addMonths(5));
            })->values();
            
            //Log::info('Is in Second Half: ' . ($leaveRecords[0]->start_date->gt($financialYearStart->copy()->addMonths(5)) ? 'Yes' : 'No'));


            $leaveReport = [
                'total_leave' => $annualLeave,
                'first_half_records' => $firstHalfRecords,
                'second_half_records' => $secondHalfRecords,
                'available_leave' => $availableLeave,
                'total_leave_by_category' => $leaveByCategory,
            ];
        }

        // Process leaves
        $leaves->each(function ($leave) {
            $user = $leave->user;

            if ($user) {
                $leave->first_name = $user->first_name;
                $leave->last_name = $user->last_name;
                $leave->role = $user->role;
            }

            $creatorName = $leave->user_id === $leave->created_by ? 'Self' : User::where('id', $leave->created_by)->value('first_name').' '.User::where('id', $leave->created_by)->value('last_name');
            $leave->creator_name = $creatorName;
        });

        // Remove unnecessary fields
        $leaves->makeHidden(['user']);

        return response()->json(['leave_data' => $leaves, 'leave_report' => $leaveReport]);
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
        $leave->user_name =  $user->first_name . ' ' . $user->last_name;
        $leave->creator_name = $creator->first_name . ' ' . $creator->last_name;

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
            'complimentary_date' => 'nullable|date',
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
            'complimentary_date' => $request->complimentary_date,
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

    public function recentLeaves()
    {
        $recentRequest = Leave::where('approval_status', '=', 'pending')
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();

        $data = [];

        foreach ($recentRequest as $recent) {
            $userData = [
                'name' => $recent->user->first_name .' '. $recent->user->last_name,
                'email' => $recent->user->email
            ];
            $data[] = [
                'title' => $recent->title,
                'start_date' => $recent->start_date,
                'description' => $recent->description,
                'status' => $recent->approval_status,
                'user' => $userData
            ];
        }

        return response()->json(['data' => $data], 200);
    }

}
