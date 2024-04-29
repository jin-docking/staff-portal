<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       
        $user = User::with(['userMeta', 'role'])->orderBy('first_name', 'ASC')->get();

        return response()->json($user);

    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::with(['userMeta', 'role:id,title'])->findorFail($id);
        
        if (empty($user)){
            return response()->json(['message' => 'user not found'], 404);
        }

        $user->userMeta->profile_pic = asset('storage/' . $user->userMeta->profile_pic);
        //$usermeta = $user->userMeta;

        return response()->json($user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function userProfile()
    {   
        $user = Auth::user();

        $role = $user->role;

        $userMeta = $user->userMeta;

        $user->userMeta->profile_pic = asset('storage/' . $user->userMeta->profile_pic);

        return response()->json($user);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        
       
        $user = Auth::user();
         
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->id == $id || $user->role->title == 'Admin') {
            
            if (User::where('id', $id)->exists())
            {
                $user = User::findOrFail($id);

                $user->update([
                    'first_name' => $request->input('first_name', $user->first_name),
                    'last_name' => $request->input('last_name', $user->last_name),
                    'email' => $request->input('email', $user->email),
                    'password' => $request->filled('password') ? Hash::make($request->input('password')) : $user->password,
                    //'role' => $request->input('role', $user->role),
                ]);

                $role = Role::find($request->input('role_id', $user->role_id));

                if (!$role) {
                    return response()->json(['error' => 'Role not found'], 404);
                }

                $user->role()->associate($role);
                $user->save();
            
                if ($request->hasFile('profile_pic') && $request->file('profile_pic')->isValid()) {
                    
                    Storage::disk('public')->delete($user->userMeta->profile_pic);

                    $imagePath = $request->file('profile_pic')->store('profile_pic', 'public');

                } else {
                    $imagePath = $user->userMeta->profile_pic;
                }
            
                $user->userMeta()->update([
                    'address' => $request->input('address', $user->userMeta->address),
                    'contact_no' => $request->input('contact_no', $user->userMeta->contact_no),
                    'gender' => $request->input('gender', $user->userMeta->gender),
                    'join_date' => $request->input('join_date', $user->userMeta->join_date),
                    'date_of_birth' => $request->input('date_of_birth', $user->userMeta->date_of_birth),
                    'father' => $request->input('father', $user->userMeta->father),
                    'mother' => $request->input('mother', $user->userMeta->mother),
                    'marital_status' => $request->input('marital_status', $user->userMeta->marital_status),
                    'spouse' => $request->input('spouse', $user->userMeta->spouse),
                    'children' => $request->input('children', $user->userMeta->children),
                    'pincode' => $request->input('pincode', $user->userMeta->pincode),
                    'aadhar' => $request->input('aadhar', $user->userMeta->aadhar),
                    'pan' => $request->input('pan', $user->userMeta->pan),
                    'profile_pic' => $imagePath,
                ]);
            return response()->json(['message' => 'user updated'], 200);

            } else {

                return response()->json(['message' => 'user not found'], 404);
            }

        } else {
            return response()->json(['message' => 'Unauthorized action'], 403);
        }

    }


    public function userCount()
    {
        $user = User::all();

        $count = $user->count();

        return response()->json(['user_count' => $count], 200);

    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (User::where('id', $id)->exists()) {

            User::findOrFail($id)->delete();
            return response()->json(['message' => 'user deleted'], 202);

        } else {

            return response()->json(['message' => 'user not found'], 404);
        }
        
    }
}
