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

        $location = LocationMeta::create([
            'user_id' => $user->id,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'location_time' => $request->input('location_time'),
        ]);

        return response()->json([
            'message' => 'location saved successfully',
            'data' => $location
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {

        // Fetch the location data for the user for the last 7 days
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $locations = DB::table('location_metas')
            ->where('user_id', $id)
            ->where('location_time', '>=', $sevenDaysAgo)
            ->orderBy('location_time')
            ->get();

        if ($locations->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $proximityThreshold = 1; // 1 km
        $dailyData = [];
        $currentGroup = [];
        $currentDate = Carbon::parse($locations->first()->location_time)->toDateString();
        $currentLocation = null;

        foreach ($locations as $location) {
            $locationDate = Carbon::parse($location->location_time)->toDateString();

            if ($currentDate !== $locationDate) {
                $this->processGroup($currentGroup, $currentLocation, $dailyData, $currentDate);
                $currentGroup = [];
                $currentDate = $locationDate;
            }

            if ($currentLocation && $this->calculateDistance($currentLocation->latitude, $currentLocation->longitude, $location->latitude, $location->longitude) > $proximityThreshold) {
                $this->processGroup($currentGroup, $currentLocation, $dailyData, $currentDate);
                $currentGroup = [];
            }

            $currentGroup[] = $location;
            $currentLocation = $location;
        }

        // Process the last group
        $this->processGroup($currentGroup, $currentLocation, $dailyData, $currentDate);

        return response()->json(['data' => $dailyData]);
    }

    private function processGroup(&$group, $location, &$dailyData, $date)
    {
        if (!empty($group) && count($group) > 1) {
            $timeSpent = $this->calculateTimeSpentInGroup($group);
            if ($timeSpent > 0) {
                $dailyData[$date][] = [
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'time_spent_minutes' => $timeSpent,
                    'time_spent_readable' => $this->formatTimeSpent($timeSpent)
                ];
            }
        }
    }

    // Calculate the Haversine distance between two points in kilometers
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Distance in kilometers
    }

    // Calculate the total time spent in a group of location entries
    private function calculateTimeSpentInGroup($group)
    {
        if (count($group) < 2) {
            // Not enough data to calculate time spent
            return 0;
        }

        $firstEntry = Carbon::parse($group[0]->location_time);
        $lastEntry = Carbon::parse(end($group)->location_time);

        return $lastEntry->diffInMinutes($firstEntry);
    }

    // Format the time spent in minutes into a human-readable format (hours and minutes)
    private function formatTimeSpent($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%d hours %d minutes', $hours, $remainingMinutes);
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
