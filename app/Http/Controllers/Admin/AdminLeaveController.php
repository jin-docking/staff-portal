<?php

namespace App\Http\Controllers\Admin;

use App\Models\Leave;
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

    return response()->json($leaves);
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


}
