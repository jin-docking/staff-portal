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
 

    public function show(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json(['message' => 'user not found']);
        }

        $startDate = $request->input('login_date') ? Carbon::parse($request->input('login_date'))->startOfDay() : Carbon::now()->startOfDay();
        $endDate = $startDate->copy()->endOfDay();

        $loginLogs = LoginLog::where('user_id', $user->id)
        ->whereBetween('login_at', [$startDate, $endDate])
        ->orderBy('login_at')
        ->get();

        $result = [];

        for ($i = 0; $i < $loginLogs->count(); $i++)
        {
            if($loginLogs[$i]->logout_at == null && !empty($loginLogs[$i+1])){
                $logoutTime = $loginLogs[$i+1]->login_at;
            } else {
                $logoutTime = $loginLogs[$i]->logout_at ? $loginLogs[$i]->logout_at : $endDate;
            }
            
            
            $filteredLocations = LocationMeta::where('user_id', $user->id)
                                            ->whereBetween('location_time', [$loginLogs[$i]->login_at, $logoutTime])
                                            ->orderBy('location_time')
                                            ->get();
            
            $timeSpent = [];

            for ($j = 0; $j < $filteredLocations->count(); $j++)
            {
                if (($j + 1) == $filteredLocations->count() && $logoutTime == $endDate) {
                    $currentLocation = $filteredLocations[$j];
                    $timeDifference = 'N/A';

                } else {
                    $currentLocation = $filteredLocations[$j]; 
                    $nextTime = ($j + 1 < $filteredLocations->count()) 
                              ? $filteredLocations[$j + 1]->location_time 
                              : $logoutTime;
                              
                    $timeDifference = gmdate('H:i:s',Carbon::parse($filteredLocations[$j]->location_time)
                                            ->diffInSeconds(Carbon::parse($nextTime)));
                }

                // $timeDifference = Carbon::parse($filteredLocations[$j]->location_time)
                //                             ->diffInSeconds(Carbon::parse($filteredLocations[$j + 1]->location_time));
                
                $timeSpent[] = [
                    'location' => $currentLocation,
                    'time_spent' => $timeDifference
                ];
                
            }
            $result[] = [
                'login_time' => $loginLogs[$i]->login_at,
                'logout_time' => $loginLogs[$i]->logout_at,
                'locations' => array_reverse($timeSpent),
            ];
        }
        return response()->json(array_reverse($result));
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
