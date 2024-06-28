<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogActivityController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $sevenDaysAgo = Carbon::now()->subDays(7);

        $loginData = LoginLog::where('user_id', $user->id)
                            ->where('login_at', '>=', $sevenDaysAgo)
                            ->orderBy('login_at', 'desc')
                            ->get();

        return response()->json(['data' => $loginData]);
    }


    public function show($id)
    {

        $sevenDaysAgo = Carbon::now()->subDays(7);

        $loginData = LoginLog::where('user_id', $id)
                            ->where('login_at', '>=', $sevenDaysAgo)
                            ->orderBy('login_at', 'desc')
                            ->get();

        return response()->json(['data' => $loginData]);
        
    }
}
