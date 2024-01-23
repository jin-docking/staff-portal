<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;
use Validator;


class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $team = Team::all();
        return response()->json($team);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'team_name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'project_manager_id' => 'required|integer',
            'frontend_team_lead_id' => 'required|integer',
            'backend_team_lead_id' => 'required|integer',
            
        ]);


        if($validator->fails()){
            return response()->json($validator->errors());       
        }

        $team = Team::create([
            'team_name' => $request->team_name,
            'description' => $request->description,
            'project_manager_id' => $request->project_manager_id,
            'frontend_team_lead_id' => $request->frontend_team_lead_id,
            'backend_team_lead_id' => $request->backend_team_lead_id,
         ]);
         return response()->json(['message' => 'Team Created'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $team = Team::findOrFail($id);
        $user = $team->user;

        return response()->json($team);
    }

    
    public function assignUser(Request $request, $team_id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $team = Team::findOrFail($team_id);
        $team->user()->attach($request->user_id);

        return response()->json(['message' => 'User assigned to team successfully'], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        
        if (Team::where('id', $id)->exists())
        {

            $team = Team::findOrFail($id);

            $user->update([
                'team_name' => is_null($request->team_name) ? $team->team_name : $request->team_name,
                'description' => is_null($request->description) ? $team->description : $request->description,
                'project_manager_id' => is_null($request->project_manager_id) ? $team->project_manager_id : $request->project_manager_id,
                'frontend_team_lead_id' => is_null($request->frontend_team_lead_id) ? $team->frontend_team_lead_id : $request->frontend_team_lead_id,
                'backend_team_lead_id' => is_null($request->backend_team_lead_id) ? $team->backend_team_lead_id : $request->backend_team_lead_id,
            ]);

            return response()->json(['message' => 'team updated'], 200);

        } else {

            return response()->json(['message' => 'team not found'], 404);

        }
        
    }


    // public function detachUser(Request $request, $team_id, $user_id)
    // {
       
    //     $team = Team::findOrFail($team_id);
    //     $user = User::findOrFail($user_id);

    //     $team->user()->datach($user->id);

    //     return response()->json(["message" => "User removed from team"]);
    // }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

        if (Team::where('id', $id)->exists()) {

            $team = Team::findOrFail($id);

            $team->user()->detach();

            $team->delete();

            return response()->json(['message' => 'team deleted'], 202);
        
        } else {

            return response()->json(['message' => 'team not found'], 404);
        }
    }
}
