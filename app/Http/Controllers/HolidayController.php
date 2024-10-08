<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class HolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /*if (Holiday::all()->empty()){
            return response()->json(['message' => 'Holiday does not exists'], 404);
        }*/

        $holiday = Holiday::all();

        return response()->json($holiday);
        
    }

    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable',
            'restricted_holiday' => 'required|in:yes,no',
        ]);

        $holiday = Holiday::create([
            'title' => $request->input('title'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'description' => $request->input('description'),
            'restricted_holiday' => $request->input('restricted_holiday'),
        ]);

        return response()->json(['message' => 'Holiday has been created'], 200);
        
    }

    public function upcomingHolidays()
    {
        $currrentDate = now();
        $nextMonth = now()->addMonth();

        $upcomingHoliday = Holiday::whereBetween('start_date', [$currrentDate, $nextMonth])->limit(3)->get();

        return response()->json(['data' => $upcomingHoliday], 200);
    }
    
    /**
     * Display the specified resource.
     */
    public function show($id)
    { 
        if (!Holiday::where('id', $id)->exists()){
            return response()->json(['message' => 'Holiday does not exists'], 404);
        }

        $holiday = Holiday::findOrFail($id);

        return response()->json(['holiday' => $holiday], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        
        if(!Holiday::where('id', $id)->exists()){
            return response()->json(['message' => 'Holiday does not exists'], 404);

        }
       

        $holiday = Holiday::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable',
            'restricted_holiday' => 'required|in:yes,no',
        ]);

        $holiday->update([
            'title' => $request->input('title', $holiday->title),
            'start_date' => $request->input('start_date', $holiday->start_date),
            'end_date' => $request->input('end_date', $holiday->end_date),
            'description' => $request->input('description', $holiday->description),
            'restricted_holiday' => $request->input('restricted_holiday', $holiday->restricted_holiday),
        ]);

        return response()->json(['message' => 'Holiday updated successfully!'], 200);
    }

    public function upcomingRestrictedHolidays() {
        
        $now = Carbon::now();
        $restrictedHolidays = Holiday::where('restricted_holiday', 'yes')->where('start_date', '>=', $now)->get();

        return response()->json(['data' => $restrictedHolidays], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!Holiday::where('id', $id)->exists()){
            return response()->json(['message' => 'Holiday does not exists'], 404);
        }
           
        $holiday = Holiday::findOrFail($id);

        $holiday->delete();

        return response()->json(['message' => 'Holiday deleted '], 200);
 
    }
}
