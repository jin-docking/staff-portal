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
    public function index(Request $request)
    {
        $user = Auth::user();

        // if (!$user) {
        //     return response()->json(['message' => 'user not found']);
        // }

        $startDate = $request->input('login_date') ? Carbon::parse($request->input('login_date'))->startOfDay() : Carbon::now()->startOfDay();
        $endDate = $startDate->copy()->endOfDay();

         // Get the first login and last logout of the day
        $firstLoginLog = LoginLog::where('user_id', $user->id)
            ->whereBetween('login_at', [$startDate, $endDate])
            ->orderBy('login_at')
            ->first();

        $lastLogoutLog = LoginLog::where('user_id', $user->id)
            ->whereBetween('login_at', [$startDate, $endDate])
            ->orderBy('login_at', 'desc')
            ->first();

        if (!$firstLoginLog) {
            return response()->json(['message' => 'No logins found for this date.']);
        }

    
        $logoutTime = $lastLogoutLog && $lastLogoutLog->logout_at ? $lastLogoutLog->logout_at : $endDate;

        // Fetch locations between login and logout times
        $filteredLocations = LocationMeta::where('user_id', $user->id)
            ->whereBetween('location_time', [$firstLoginLog->login_at, $logoutTime])
            ->orderBy('location_time')
            ->get();


        $timeSpent = [];

        for ($j = 0; $j < $filteredLocations->count(); $j++) {
            if (($j + 1) == $filteredLocations->count() && $logoutTime == $endDate) {
                $currentLocation = $filteredLocations[$j];
                $timeDifference = 'Current Location';

            } else {         

                $currentLocation = $filteredLocations[$j];
                $nextTime = ($j + 1 < $filteredLocations->count())
                    ? $filteredLocations[$j + 1]->location_time
                    : $logoutTime;

                $timeDifference = gmdate('H:i:s', Carbon::parse($filteredLocations[$j]->location_time)
                ->diffInSeconds(Carbon::parse($nextTime)));
                
            }
        
            $timeSpent[] = [
                'location' => $currentLocation,
                'location_day' => date('l', strtotime($currentLocation->location_time)),
                'time_spent' => $timeDifference
            ];
        }

        $result = [
        'login_time' => $firstLoginLog->login_at,
        'login_day' => date('l', strtotime($firstLoginLog->login_at)),
        'logout_time' => $logoutTime,
        'logout_day' => $logoutTime ? date('l', strtotime($logoutTime)) : 'N/A',
        'locations' => array_reverse($timeSpent),
        ];

         return response()->json($result);

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

        $latestLogin = LoginLog::where('user_id', $user->id)
        ->orderBy('login_at', 'desc')
        ->first();
        
        $latestLocation = LocationMeta::where('user_id', $user->id)
        ->where('location_time', '>=', $latestLogin->login_at)
        ->orderBy('location_time', 'desc')
        ->first();

        $newLocationTime = Carbon::parse($request->input('location_time'));
        $alertMessage = null;

        //$distance = $this->haversine($latestLocation->latitude, $latestLocation->longitude, $request->input('latitude'), $request->input('longitude'));

        
        if ($latestLocation) {
            $distance = $this->haversine($latestLocation->latitude, $latestLocation->longitude, $request->input('latitude'), $request->input('longitude'));
    
            if ($distance > 10) {
                $latestLocationTime = Carbon::parse($latestLocation->location_time);
    
                // Calculate the time difference in minutes
                $timeDifference = $latestLocationTime->diffInMinutes($newLocationTime);
    
                if ($timeDifference <= 2) {
                    // Set the alert message
                    $alertMessage = 'Travelling Mode';
                }
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
     * Function to calculate distance using lat and long
     */

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        // distance between latitudes and longitudes
        $dLat = ($lat2 - $lat1) * M_PI / 180.0;
        $dLon = ($lon2 - $lon1) * M_PI / 180.0;

        // convert to radians
        $lat1 = ($lat1) * M_PI / 180.0;
        $lat2 = ($lat2) * M_PI / 180.0;

        // apply formulae
        $a = pow(sin($dLat / 2), 2) + pow(sin($dLon / 2), 2) * cos($lat1) * cos($lat2);
        $rad = 6371; // radius of the Earth in kilometers
        $c = 2 * asin(sqrt($a));
        $distance_km = $rad * $c;

        // convert kilometers to meters
        $distance_m = $distance_km * 1000;
        return $distance_m;
    }


    /**
     * Display the specified resource.
     */
 

    public function show(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // if (!$user) {
        //     return response()->json(['message' => 'user not found']);
        // }

        $startDate = $request->input('login_date') ? Carbon::parse($request->input('login_date'))->startOfDay() : Carbon::now()->startOfDay();
        $endDate = $startDate->copy()->endOfDay();

        // Get the first login and last logout of the day
        $firstLoginLog = LoginLog::where('user_id', $user->id)
            ->whereBetween('login_at', [$startDate, $endDate])
            ->orderBy('login_at')
            ->first();

        $lastLogoutLog = LoginLog::where('user_id', $user->id)
            ->whereBetween('login_at', [$startDate, $endDate])
            ->orderBy('login_at', 'desc')
            ->first();

        if (!$firstLoginLog) {
            return response()->json(['message' => 'No logins found for this date.']);
        }

        // Determine the logout time
        $logoutTime = $lastLogoutLog && $lastLogoutLog->logout_at ? $lastLogoutLog->logout_at : $endDate;

        // Get locations between the first login and last logout
        $filteredLocations = LocationMeta::where('user_id', $user->id)
            ->whereBetween('location_time', [$firstLoginLog->login_at, $logoutTime])
            ->orderBy('location_time')
            ->get();

        $timeSpent = [];

        for ($j = 0; $j < $filteredLocations->count(); $j++) {
            if (($j + 1) == $filteredLocations->count() && $logoutTime == $endDate) {
                $currentLocation = $filteredLocations[$j];
                $timeDifference = 'Current Location';      

            } else {           
                $currentLocation = $filteredLocations[$j];
                $nextTime = ($j + 1 < $filteredLocations->count())
                    ? $filteredLocations[$j + 1]->location_time
                    : $logoutTime;                
        

                $timeDifference = gmdate('H:i:s', Carbon::parse($filteredLocations[$j]->location_time)
                ->diffInSeconds(Carbon::parse($nextTime)));
                
            }

            $timeSpent[] = [
                'location' => $currentLocation,
                'location_day' => date('l', strtotime($currentLocation->location_time)),
                'time_spent' => $timeDifference
            ];
        }


        $result = [
        'login_time' => $firstLoginLog->login_at,
        'login_day' => date('l', strtotime($firstLoginLog->login_at)),
        'logout_time' => $logoutTime,
        'logout_day' => $logoutTime ? date('l', strtotime($logoutTime)) : 'N/A',
        'locations' => array_reverse($timeSpent),
        ];

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
