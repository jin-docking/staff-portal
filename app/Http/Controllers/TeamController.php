<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\User;
use App\Models\Role;
use Validator;
use Auth;


class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $team = Team::with('user.role:id,title')->get();
        //return response()->json($team);
        //$team = Team::with(['user', 'projectManager', 'frontendTeamLead', 'backendTeamLead'])->get();
        return response()->json($team);
    }

    public function userTeamList()
    {
        $user = Auth::user();

        if (!$user) 
        {
                return response()->json(['message' => 'User not authenticated'], 401);
        }

        $teams = $user->teams()->get();

        if ($teams->isEmpty()) {
            return response()->json(['message' => 'User does not belong to any team'], 404);
        }

        return response()->json($teams);
    }


    public function userTeam($id)
    {
    
        $teams = Team::with(['user.role', 'user.userMeta'])->find($id);
        
        if (!$teams) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        $hierarchies = [];

        
            $hierarchy = [
                'team_id' => $teams->id,
                'team_name' => $teams->team_name,
                'description' => $teams->description,
                'project_manager' => [],
                'frontend_team_lead' => [],
                'backend_team_lead' => [],
                'frontend_developers' => [],
                'backend_developers' => []
            ];

            foreach ($teams->user as $member) {
                if (strtolower($member->role->title) == 'project manager') {
                    $hierarchy['project_manager'] = $member;
                } elseif (strtolower($member->role->title) == 'frontend teamlead') {
                    $hierarchy['frontend_team_lead'] = $member;
                } elseif (strtolower($member->role->title) == 'backend teamlead') {
                    $hierarchy['backend_team_lead'] = $member;
                } elseif (strpos(strtolower($member->role->title), 'frontend') !== false) {
                    $hierarchy['frontend_developers'][] = $member;
                } else {
                    $hierarchy['backend_developers'][] = $member;
                }
            }

            $hierarchies[] = $hierarchy;
       

        return response()->json(['teams' => $hierarchies]);
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
            'frontend_developers' => 'required|array',
            'frontend_developers.*' => 'exists:users,id',
            'backend_developers' => 'required|array',
            'backend_developers.*' => 'exists:users,id',
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
    
        $team->user()->attach($request->input('frontend_developers'));
        $team->user()->attach($request->input('backend_developers'));
    
        return response()->json(['message' => 'Team Created'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        
        $team = Team::with('user.role:id,title')->find($id);

        /*$teamMembersWithRoles = $team->user->map(function ($member) {
             return [
                'user_id' => $member->id,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name, 
                'role' => $member->role->title, 
            ];
         });*/
        
        return response()->json(['data' => $team]);
        
    }


    public function getUsersByRole($role)
    {
    
        $roles = Role::where('title', 'like', '%' . $role . '%')->get();

        if ($roles->isEmpty()) {
            return response()->json(['message' => 'No matching roles found'], 404);
        }

        
        $users = collect();

        foreach ($roles as $roleModel) {
            $users = $users->merge($roleModel->users);
        }

        return response()->json(['users' => $users->unique()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        
        if (Team::where('id', $id)->exists())
        {
            $request->validate([
                'team_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'project_manager_id' => 'required|exists:users,id',
                'frontend_team_lead_id' => 'required|exists:users,id',
                'backend_team_lead_id' => 'required|exists:users,id',
                'frontend_developers' => 'required|array',
                'frontend_developers.*' => 'exists:users,id',
                'backend_developers' => 'required|array',
                'backend_developers.*' => 'exists:users,id',
            ]);
        
            $team = Team::findOrFail($id);
        
            $team->update([
                'team_name' => $request->input('team_name'),
                'description' => $request->input('description'),
                'project_manager_id' => $request->input('project_manager_id'),
                'frontend_team_lead_id' => $request->input('frontend_team_lead_id'),
                'backend_team_lead_id' => $request->input('backend_team_lead_id'),
            ]);
        
            $members = [
                $request->input('project_manager_id') => [],
                $request->input('frontend_team_lead_id') => [],
                $request->input('backend_team_lead_id') => [],
            ];
        
            foreach ($request->input('frontend_developers') as $developerId) {
                $members[$developerId] = [];
            }
        
            foreach ($request->input('backend_developers') as $developerId) {
                $members[$developerId] = [];
            }
        
            $team->user()->sync($members);
        
            return response()->json(['message' => 'Team Updated'], 200);

        } else {

            return response()->json(['message' => 'Team not found'], 404);

        }
    }


    public function teamCount()
    {

        $team = Team::all();

        $team_count = $team->count();

        return response()->json(['data' => $team_count], 200);
    }

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

    public function showDevelopers($id)
    {
        $team = Team::with('user.role:id,title')->find($id);

        $frontendDevelopers = [];
        $backendDevelopers = [];

        $teamMembersWithRoles = $team->user->map(function ($member) use (&$frontendDevelopers, &$backendDevelopers) {
            $userData = [
                'user_id' => $member->id,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'email' => $member->email,
                'role' => $member->role->title,
            ];

            // Determine role and add to respective array
            if (strtolower($member->role->title) == 'frontend developer') {
                $frontendDevelopers[] = $userData;
            } elseif (strtolower($member->role->title) == 'backend developer') {
                $backendDevelopers[] = $userData;
            }

            return $userData;
        });

        $responseData = [
            'data' => $team,
            'frontend_developers' => $frontendDevelopers,
            'backend_developers' => $backendDevelopers,
        ];

        return response()->json($responseData);
    }
}
