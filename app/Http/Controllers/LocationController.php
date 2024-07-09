<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LocationMeta;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Auth;
Use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        // Define the start and end dates for the last 7 days including today
        $endDate = Carbon::now('Asia/Kolkata');
        $startDate = $endDate->copy()->subDays(7);

        // Retrieve login and logout times for the user within the last 7 days including today
        $loginLogs = LoginLog::where('user_id', $id)
                             ->whereBetween('login_at', [$startDate, $endDate])
                             ->orWhereBetween('logout_at', [$startDate, $endDate])
                             ->orderBy('login_at', 'desc')
                             ->get();
 
        $result = [];
        //return response()->json($loginLogs);

        // Filter location data within login and logout times
        foreach ($loginLogs as $log) {
            if ($log->logout_at !== null) {
                $logoutTime = $log->logout_at;
            }else {
                $logoutTime = Carbon::now('Asia/Kolkata');
            }

            $filteredLocations = LocationMeta::where('user_id', $id)
                                             ->whereBetween('location_time', [$log->login_at, $logoutTime])
                                             ->orderBy('location_time', 'desc')
                                             ->get();

            $groupedData = $filteredLocations->groupBy(function($item) {
                return Carbon::parse($item->location_time)->format('Y-m-d');
            });

            foreach ($groupedData as $date => $locations) {
                $timeSpent = [];

                foreach ($locations as $index => $location) {
                    if ($index < $locations->count() - 1) {
                        $nextLocation = $locations[$index + 1];
                        $timeDifference = Carbon::parse($location->location_time)
                                                ->diffInSeconds(Carbon::parse($nextLocation->location_time));
                        
                        $timeSpent[] = [
                            'location' => $location,
                            'time_spent' => gmdate('H:i:s', $timeDifference)
                        ];
                    } else {
                        // For the last location of the session, use logout time to calculate time spent
                        $timeDifference = Carbon::parse($location->location_time)
                                                ->diffInSeconds(Carbon::parse($logoutTime));
                        
                        $timeSpent[] = [
                            'location' => $location,
                            'time_spent' => gmdate('H:i:s', $timeDifference)
                        ];
                    }
                }

                $result[$date][] = [
                    'login_time' => $log->login_at,
                    'logout_time' => $log->logout_at,
                    'locations' => $timeSpent
                ];
            }
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
