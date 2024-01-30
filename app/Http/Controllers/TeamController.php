<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\User;
use Validator;
use Auth;


class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $team = Team::all();
        // return response()->json($team);
        //$team = Team::with(['user', 'projectManager', 'frontendTeamLead', 'backendTeamLead'])->get();
        return response()->json($team);
    }


    public function userTeam()
    {
        $user = Auth::user();

        if ($user->teams()->exists()) {
            
            $team = $user->teams()->with('user')->get();

            return response()->json($team);
        } else {
            
            return response()->json(['message' => 'team not found'], 404);
        }

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
        
         $request->validate([
            'team_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_manager_id' => 'required|exists:users,id',
            'frontend_team_lead_id' => 'required|exists:users,id',
            'backend_team_lead_id' => 'required|exists:users,id',
            'user_id' => 'required|array',
            'user_id.*' => 'exists:users,id',
        ]);

        
        $team = Team::create([
            'team_name' => $request->input('team_name'),
            'description' => $request->input('description'),
            'project_manager_id' => $request->input('project_manager_id'),
            'frontend_team_lead_id' => $request->input('frontend_team_lead_id'),
            'backend_team_lead_id' => $request->input('backend_team_lead_id'),
        ]);
        $team->user()->attach([
            $request->input('project_manager_id'),
            $request->input('frontend_team_lead_id'),
            $request->input('backend_team_lead_id'),
        ]);

        $team->user()->attach($request->input('user_id'));

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

    public function getUsersByRole($role)
    {
        $users = User::where('role', $role)->get(['id', 'first_name', 'last_name']);

        return response()->json($users);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        
        if (Team::where('id', $id)->exists())
        {
            $request->validate([
                'team_name' => 'required|string',
                'description' => 'nullable|string',
                'project_manager_id' => 'required|exists:users,id',
                'frontend_team_lead_id' => 'required|exists:users,id',
                'backend_team_lead_id' => 'required|exists:users,id',
                'user_id' => 'array',
            ]);

            
            $team = Team::findOrFail($id);

            
            $team->update([
                'team_name' => $request->input('team_name'),
                'description' => $request->input('description'),
                'project_manager_id' => $request->input('project_manager_id'),
                'frontend_team_lead_id' => $request->input('frontend_team_lead_id'),
                'backend_team_lead_id' => $request->input('backend_team_lead_id'),
            ]);

            $userIds = [
                $request->input('project_manager_id'),
                $request->input('frontend_team_lead_id'),
                $request->input('backend_team_lead_id'),
            ];
    
            
            if ($request->has('user_id')) {
                $userIds = array_merge($userIds, $request->input('user_id'));
            }
    
            
            $team->user()->sync($userIds);
            
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
