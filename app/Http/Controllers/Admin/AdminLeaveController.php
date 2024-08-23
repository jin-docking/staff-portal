<?php

namespace App\Http\Controllers\Admin;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
//use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\LeaveNotificationMail;
use App\Models\CompanyInfo;


class AdminLeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get request parameters for year, month, day, user Id, status and category
        $year = $request->input('year');
        $month = $request->input('month');
        $day = $request->input('day');
        $userId = $request->input('user_id');
        $status = $request->input('status');
        $category = $request->input('category');

        // Start query with base condition and eager load users
        $query = Leave::with('user');

        $currentDate = Carbon::now();
        //return response()->json($currentYear);

        // Set financial year dates
        //$financialYearStart = Carbon::createFromDate($year, 4, 1);
        //$financialYearEnd = Carbon::createFromDate($year + 1, 3, 31);
        if ($year == $currentDate->year) {
            $financialYearStart = $currentDate->month >= 4 ? $currentDate->startOfYear()->addMonths(3) : $currentDate->subYear()->startOfYear()->addMonths(3);
            $financialYearEnd = $financialYearStart->copy()->addYear()->subDay();
        } else {
            $financialYearStart = Carbon::createFromDate($year, 4, 1);
            $financialYearEnd = Carbon::createFromDate($year + 1, 3, 31);
        }
        

        if ($year) {
            $query->whereYear('created_at', $year);
        }

        if ($month) {
            $query->whereMonth('start_date', $month);
        }

        if ($day) {
            $dayDate = Carbon::createFromDate($year, $month, $day);
            //$query->whereDate('start_date', '>=', $dayDate);
            $query->where(function($q) use ($dayDate) {
                $q->whereDate('start_date', '<=', $dayDate)
                  ->whereDate('end_date', '>=', $dayDate);
            });
        }
        if ($category) {
            $query->where('category', $category);
        }
        if ($status) {
            $query->where('approval_status', $status);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
    
        // Order the results with pending leaves first
        $query->orderByRaw("FIELD(approval_status, 'pending', 'approved', 'rejected'), created_at DESC");
    
        $leaves = $query->get();

        $leaveReport = [];

        
        if ($userId) {
            $leaveRecordsQuery = Leave::where('user_id', $userId)
                ->where('approval_status', '!=' ,'rejected')
                ->whereBetween('start_date', [$financialYearStart, $financialYearEnd]);

            /*if ($year) {
                // Filter leave records by financial year
                $leaveRecordsQuery->whereBetween('start_date', [$financialYearStart, $financialYearEnd]);
            }*/

            $leaveRecords = $leaveRecordsQuery->get();
            
            $user = User::find($userId);
            $annualLeave = $user->role->leaves;
            $takenLeaveCount = $leaveRecords->filter(function ($record) {
                return strtolower($record->category) != 'complementary leave' && strtolower($record->category) != 'restricted holiday';
            })->sum('leave_count');

            $availableLeave = max(0, $annualLeave - $takenLeaveCount);

            //Log::info('Leave Records Count: ' . $leaveRecords->count());

            $leaveByCategory = $leaveRecords->groupBy('category')->map(function ($leaves) {
                return $leaves->sum('leave_count');
            });
    
            $lossOfPay = $leaveRecords->where('loss_of_pay', 'true')->sum('leave_count');

            $firstHalfRecords = $leaveRecords->filter(function ($record) use ($financialYearStart) {
                return $record->start_date->lte($financialYearStart->copy()->addMonths(5));
            })->values();
    
            $secondHalfRecords = $leaveRecords->filter(function ($record) use ($financialYearStart) {
                return $record->start_date->gt($financialYearStart->copy()->addMonths(5));
            })->values();
            
            $firstHalfLeaveCount = $firstHalfRecords->filter(function ($record) {
                return strtolower($record->category) != 'complementary leave' && strtolower($record->category) != 'restricted holiday';
            })->sum('leave_count');
    
            $secondHalfLeaveCount = $secondHalfRecords->filter(function ($record) {
                return strtolower($record->category) != 'complementary leave' && strtolower($record->category) != 'restricted holiday';
            })->sum('leave_count');
            //Log::info('Is in Second Half: ' . ($leaveRecords[0]->start_date->gt($financialYearStart->copy()->addMonths(5)) ? 'Yes' : 'No'));


            $leaveReport = [
                'total_leave' => $annualLeave,
                'leave_records' => $leaveRecords,
                'first_half_records' => $firstHalfRecords,
                'first_half_leave_count' => $firstHalfLeaveCount,
                'second_half_records' => $secondHalfRecords,
                'second_half_leave_count' => $secondHalfLeaveCount,
                'available_leave' => $availableLeave,
                'total_leave_by_category' => $leaveByCategory,
                'loss_of_pay' => $lossOfPay,
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

        // $leaveCount = 0.0;
        // if ($request->input('approval_status') == 'approved') { 
        //     //if (strtolower($leave->category) !== 'complementary leave' && strtolower($leave->category) !== 'restricted holiday') {
        //         if (strtolower($leave->leave_type) == 'full day') {
        //             $startDate = Carbon::parse($leave->start_date);
        //             $endDate = Carbon::parse($leave->end_date);
        //             $leaveCount = $startDate->diffInDays($endDate) + 1.0; 
                    
        //         } else {
        //                 $leaveCount = 0.5;
        //         }
             
        // }
        

        // Update the approval status
        $leave->update([
            'approval_status' => $request->input('approval_status'),
            //'leave_count' => $leaveCount,
        ]);

        $user = User::where('id', $leave->user_id)->first();

        //Mail::to($user->email)->send(new LeaveNotificationMail($leave, $user, 'update'));
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

        if ($companyInfo) {
            $ccEmails = [$admin->email, $companyInfo->email];
            Mail::to($user->email)->send(new LeaveNotificationMail($leave, $user, 'update', $ccEmails));
        }

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
            'leave_type' => 'required|string',
            'leave_session' => 'required|string',
                        
        ]);

        $leaveCount = 0.0;

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

        if (strtolower($request->leave_type) == 'full day') {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $leaveCount = $startDate->diffInDaysFiltered(function(Carbon $date) {
                return !$date->isWeekend();
            }, $endDate) + 1.0;        
        }else {
            $leaveCount = 0.5;
        }
        
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
            'leave_count' => $leaveCount,
            'loss_of_pay' => $request->loss_of_pay,
            'leave_type' => $request->leave_type,
            'leave_session' => $request->leave_session,           

        ]);       

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

        if ($companyInfo) {
            $ccEmails = [$admin->email];
            Mail::to($companyInfo->email)->send(new LeaveNotificationMail($leave, $user, 'request', $ccEmails));
        }

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
