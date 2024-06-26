<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LocationMeta;
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

            if ($timeDifference <= 5) {
                // Set the alert message
                $alertMessage = 'User is traveling, monitor only after 15 minutes';
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
        
        $userId = $id;
    // Retrieve the latest location data for the user
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();
        $locations = LocationMeta::where('user_id', $userId)
                                 ->where('location_time', '>=', $sevenDaysAgo)
                                 ->orderBy('location_time', 'desc')
                                 ->get();

                                 //return response()->json($locations);
        // Group data by date and calculate time spent at each location
        $groupedData = $locations->groupBy(function($item) {
            return Carbon::parse($item->location_time)->format('Y-m-d');
        });

        $result = [];

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
                } /*else {
                    // For the last location of the day, we don't have a next location to compare with
                    $timeSpent[] = [
                        'location' => $location,
                        'time_spent' => "N/A"
                    ];
                }*/
            }

            $result[] = $timeSpent;
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
