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
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $locations = LocationMeta::where('user_id', $id)
                                 ->where('location_time', '>=', $sevenDaysAgo)
                                 ->orderBy('location_time', 'desc')
                                 ->get();

        // Check if the user has no locations or only one location
        if ($locations->count() < 2) {
            $currentLocation = $locations->first();

            $timeSpent = [];
            if ($currentLocation) {
                $now = Carbon::now();
                $duration = $now->diffInMinutes(Carbon::parse($currentLocation->location_time));

                $timeSpent[] = [
                    'latitude' => $currentLocation->latitude,
                    'longitude' => $currentLocation->longitude,
                    'time_spent' => $duration,
                    'location_time' => $currentLocation->location_time,
                    'now' => $now
                ];
            }

            return response()->json($timeSpent);
        }

        $timeSpent = [];

        for ($i = 0; $i < count($locations) - 1; $i++) {
            $currentLocation = $locations[$i];
            $nextLocation = $locations[$i + 1];

            $duration = Carbon::parse($nextLocation->location_time)->diffInMinutes(Carbon::parse($currentLocation->location_time));

            $timeSpent[] = [
                'latitude' => $currentLocation->latitude,
                'longitude' => $currentLocation->longitude,
                'time_spent' => $duration,
                'location_time' => $currentLocation->location_time,
                //'now' => $now
            ];
        }

        // Handle the last location entry
        $lastLocation = $locations->last();
        $now = Carbon::now();
        $duration = $now->diffInMinutes(Carbon::parse($lastLocation->location_time));

        $timeSpent[] = [
            'latitude' => $lastLocation->latitude,
            'longitude' => $lastLocation->longitude,
            'time_spent' => $duration,
            'location_time' => $lastLocation->location_time,
            'now' => $now
        ];

        return response()->json($timeSpent);
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
