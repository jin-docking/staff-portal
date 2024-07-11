<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LocationMeta;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
Use Carbon\Carbon;
//use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $sevenDaysAgo = Carbon::now()->subDays(7);

        $location = LocationMeta::where('user_id', $user->id)
                            ->where('location_time', '>=', $sevenDaysAgo)
                            ->orderBy('location_time')
                            ->get();
        return response()->json(['data' => $location]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_time' => 'required|date',
        ]);
        

        $latestLocation = LocationMeta::where('user_id', $user->id)
        ->orderBy('location_time', 'desc')
        ->first();

        $newLocationTime = Carbon::parse($request->input('location_time'));
        $alertMessage = null;

        if ($latestLocation) {
            $latestLocationTime = Carbon::parse($latestLocation->location_time);

            // Calculate the time difference in minutes
            $timeDifference = $latestLocationTime->diffInMinutes($newLocationTime);

            if ($timeDifference <= 2) {
                // Set the alert message
                //$alertMessage = 'User is traveling, monitor only after 15 minutes';
                $alertMessage = 'Travelling Mode';
            }
        }

        $location = LocationMeta::create([
            'user_id' => $user->id,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'location_time' => $request->input('location_time'),
        ]);

        $responseMessage = 'Location saved successfully';
        if ($alertMessage) {
            $responseMessage .= ' | ' . $alertMessage;
        }

        
        // Return the JSON response
        return response()->json([
            'message' => $responseMessage,
            'data' => $location
        ]);
        
    }


    /**
     * Display the specified resource.
     */
 

    public function show($id)
    {
        $user = User::findOrFail($id);
        // Define the start and end dates for the last 7 days including today
        $endDate = now('Asia/Kolkata');
        $startDate = $endDate->copy()->subDays(7);

        // Retrieve login and logout times for the user within the last 7 days including today
        $loginLogs = LoginLog::where('user_id', $user->id)
                            ->where(function($query) use ($startDate, $endDate) {
                             $query->whereBetween('login_at', [$startDate, $endDate])
                                   ->orWhereBetween('logout_at', [$startDate, $endDate]);
                            })
                            ->orderBy('login_at', 'desc')
                            ->get();
 
        $result = [];
        //return response()->json($loginLogs);

        $currentTime = now('Asia/Kolkata');
        
        // Filter location data within login and logout times
        foreach ($loginLogs as $log) {
            $logoutTime = $log->logout_at ? $log->logout_at : $currentTime;
            
            $filteredLocations = LocationMeta::where('user_id', $user->id)
                                             ->whereBetween('location_time', [$log->login_at, $logoutTime])
                                             ->orderBy('location_time')
                                             ->get();

            $timeSpent = [];
            $locationCount = $filteredLocations->count();

            //Calculate the time spent 
            for($i = 0; $i <= $locationCount - 1 ; $i++) {
                $currentLocation = $filteredLocations[$i];
                $nextTime = ($i + 1 < $locationCount) 
                            ? $filteredLocations[$i + 1]->location_time 
                            : $logoutTime;

                $timeDifference = Carbon::parse($currentLocation->location_time)
                                        ->diffInSeconds(Carbon::parse($nextTime));

                $timeSpent[] = [
                    'location' => $currentLocation,
                    'time_spent' => gmdate('H:i:s', $timeDifference)
                ];
                
            }

            $result[] = [
                'login_time' => $log->login_at,
                'logout_time' => $log->logout_at,
                'locations' => array_reverse($timeSpent),
            ];
            
        }

        return response()->json($result);
    }

    
    
   
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
