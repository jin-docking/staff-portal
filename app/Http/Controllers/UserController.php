<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\UserUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\Role;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get request parameters for skillset and user name. skilSets should be Array
        $skills = $request->input('skills');
        $firstName = $request->input('first_name');

        // Get the authenticated user
        $user = Auth::user();

        //Eager Load user's
        $query = User::with(['userMeta', 'role', 'skillSets']);

        // Get currently authenticated project managers team members
        if (strtolower($user->role->title) == 'project manager') {
            $query->whereHas('teams', function ($teamQuery) use ($user) {
                        $teamQuery->where('project_manager_id', $user->id);
                    });
        }

        

        // Apply filtering based on skills
        if ($skills && is_array($skills) && count($skills) > 0) { // Check if $skills is an array and not empty
            foreach ($skills as $skill) {
                $query->whereHas('skillSets', function ($query) use ($skill) {
                    $query->where('skill_sets.id', $skill);
                });
            }
        }

        // Apply filtering based on first name
        if ($firstName) {
            $query->where('first_name', 'like', '%' . $firstName . '%');
        }

        // Retrieve users
        $users = $query->orderBy('first_name', 'ASC')->get();

        return response()->json($users);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //selects user with metadata, role data and skillset
        $user = User::with(['userMeta', 'role:id,title', 'skillSets:id,title'])->findorFail($id);
        
        if (empty($user)){
            return response()->json(['message' => 'user not found'], 404);
        }

        // Calculate work experience
        // $joinDate = $user->userMeta->join_date;
        // $joinDate = Carbon::parse($joinDate);
        // $currentDate = now();
        // $work = $joinDate->diff($currentDate);
        $work = $this->workExp($user->userMeta->join_date);

        $years = $work->y;
        $months = $work->m;

        $workExperience = [
            'years' => $years,
            'months' => $months,
        ];
        $user->userMeta->work_experience =  $workExperience;

        //Building URL for profile image
        $user->userMeta->profile_pic = asset('storage/' . $user->userMeta->profile_pic);
        
        // Hides unnecessary fields
        $user->skillSets->makeHidden(['pivot']);

        return response()->json($user);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function userProfile()
    {   
        //selects currently logged in user with metadata, role data and skillset
        $user = Auth::user()->load('role', 'skillSets', 'userMeta');

        // Calculate work experience
        // $joinDate = $user->userMeta->join_date;
        // $joinDate = Carbon::parse($joinDate);
        // $currentDate = now('Asia/Kolkata');
        // $work = $joinDate->diff($currentDate);
        $work = $this->workExp($user->userMeta->join_date);

        $years = $work->y;
        $months = $work->m;

        $workExperience = [
            'years' => $years,
            'months' => $months,
        ];
        $user->userMeta->work_experience =  $workExperience;

        //Building URL for profile image
        $user->userMeta->profile_pic = asset('storage/' . $user->userMeta->profile_pic);

        return response()->json($user);
    }


    private function workExp($date)
    {
        $joinDate = Carbon::parse($date);
        $currentDate = now('Asia/Kolkata');

        return $joinDate->diff($currentDate);
    } 
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if the authenticated user is authorized to perform the action
        if ($user->id != $id && $user->role->title != 'Admin') {
            return response()->json(['message' => 'Unauthorized action'], 403);
        }

        // Find the user to update
        $updatedUser = User::findOrFail($id);

        // Check if the user exists
        if (!$updatedUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update basic user information
        $updatedUser->update([
            'first_name' => $request->input('first_name', $updatedUser->first_name),
            'last_name' => $request->input('last_name', $updatedUser->last_name),
            'email' => $request->input('email', $updatedUser->email),
            'password' => $request->filled('password') ? Hash::make($request->input('password')) : $updatedUser->password,
            'user_status' => $request->input('user_status', $user->user_status),
        ]);

        // Find the role to associate with the user
        if ($request->filled('role_id')) {
            $role = Role::findOrFail($request->input('role_id'));

            // Check if the role exists
            if (!$role) {
                return response()->json(['error' => 'Role not found'], 404);
            }

            // Associate the role with the user
            $updatedUser->role()->associate($role);
            $updatedUser->save();
        }

        
        if ($request->has('skill_sets')) {
            // Associate the skills with the user
            $updatedUser->skillSets()->sync($request->input('skill_sets'));
        }

        // Define user metadata fields
        $userMetaData = [
            'address', 'contact_no', 'gender', 'join_date', 'date_of_birth', 'work_title', 'father',
            'mother', 'marital_status', 'spouse', 'children', 'pincode', 'aadhar', 'pan'
        ];

        // Update user metadata
        $userDataToUpdate = [];
        foreach ($userMetaData as $field) {
            // Check if the field exists in the request, otherwise use the existing value
            if ($request->filled($field)) {
                $userDataToUpdate[$field] = $request->input($field);
            }
        }

        // Update profile picture if provided in the request
        if ($request->hasFile('profile_pic') && $request->file('profile_pic')->isValid()) {
            // Delete the old profile picture if it exists
            if ($updatedUser->userMeta->profile_pic) {
                Storage::disk('public')->delete($updatedUser->userMeta->profile_pic);
            }

            // Store the new profile picture
            $imagePath = $request->file('profile_pic')->store('profile_pic', 'public');
            $userDataToUpdate['profile_pic'] = $imagePath;
        }

        // Update user metadata fields
        if (!empty($userDataToUpdate)) {
            $updatedUser->userMeta()->update($userDataToUpdate);
        }

            // Send an email to the admin
        $admin = User::where('role_id', Role::where('title', 'Admin')->first()->id)->first();
        if ($admin) {
            Mail::to($admin->email)->send(new UserUpdated($updatedUser));
        }

        // Return success response
        return response()->json(['message' => 'User updated'], 200);
    }



    public function userCount()
    {   
        //Selects all users
        $user = User::where('user_status', '!=', 'resigned')->get();

        //Take count of all users 
        $count = $user->count();

        return response()->json(['user_count' => $count], 200);

    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        //Check if given user exists
        if ($user->id == $id) {
            return response()->json(['message' => 'user cannot be deleted'], 400);
        }
        
        if (User::where('id', $id)->exists()) {
            //Find and delete the user
            User::findOrFail($id)->delete();
            return response()->json(['message' => 'user deleted'], 202);

        } else {

            return response()->json(['message' => 'user not found'], 404);
        }
        
    }
}
