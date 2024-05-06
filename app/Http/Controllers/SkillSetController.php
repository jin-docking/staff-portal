<?php

namespace App\Http\Controllers;

use App\Models\SkillSet;
use Illuminate\Http\Request;

class SkillSetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $skillSet = SkillSet::orderBy('title', 'asc')->get();

        return response()->json($skillSet);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        $skillSet = SkillSet::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
        ]);

        return response()->json(['message' => 'skill added successfully', 'data' => $skillSet], 200);

    }

    /**
     * Display the specified resource.
     */
    public function show(SkillSet $skillSet)
    {
        return response()->json($skillSet);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SkillSet $skillSet)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        $skillSet->update([
            'title' => $request->input('title', $skillSet->title),
            'description' => $request->input('description', $skillSet->description), 
        ]);

        return response()->json(['message' => 'Sill updated succesfully'], 204);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SkillSet $skillSet)
    {
        $skillSet->delete();

        return response()->json(['message' => 'Skill Deleted successfull']);
    }
}
