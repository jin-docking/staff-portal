<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
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
       
        $user = User::with('userMeta')->get();

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
        $user = User::with('role:id,title')->findorFail($id);
        if (empty($user)){
            return response()->json(['message' => 'user not found'], 404);
        }
        
        $usermeta = $user->userMeta;
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
        return response()->json(['user' => $user]);
    }


    public function getProfilePic($userId)
    {
        $user = User::findOrFail($userId);

        
        $imageName = $user->userMeta->profile_pic;

        $imagePath = public_path('images/') . $imageName;

        
        if (!file_exists($imagePath)) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        
        return response()->file($imagePath);
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
                    $image = $request->file('profile_pic');
                    $imageName = time() . '.' . $image->extension();
                    $image->move(public_path('images'), $imageName);
                } else {
                    $imageName = $user->userMeta->profile_pic;
                }
            
                $user->userMeta()->update([
                    'address' => $request->input('address', $user->userMeta->address),

                    'gender' => $request->input('gender', $user->userMeta->gender),
                    'join_date' => $request->input('join_date', $user->userMeta->join_date),
                    'date_of_birth' => $request->input('date_of_birth', $user->userMeta->date_of_birth),
                    'father' => $request->input('father', $user->userMeta->father),
                    'mother' => $request->input('mother', $user->userMeta->mother),
                    'spouse' => $request->input('spouse', $user->userMeta->spouse),
                    'children' => $request->input('children', $user->userMeta->children),
                    'pincode' => $request->input('pincode', $user->userMeta->pincode),
                    'aadhar' => $request->input('aadhar', $user->userMeta->aadhar),
                    'pan' => $request->input('pan', $user->userMeta->pan),
                    'profile_pic' => $imageName,
                ]);
            return response()->json(['message' => 'user updated'], 200);

            } else {

                return response()->json(['message' => 'user not found'], 404);
            }

        } else {
            return response()->json(['message' => 'Unauthorized action'], 403);
        }



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
