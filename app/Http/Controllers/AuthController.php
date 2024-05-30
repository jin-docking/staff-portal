<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Role;

class AuthController extends Controller
{
    //Method for registering user
    public function register(Request $request)
    {
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id', 
            'skills_sets' => 'nullable',
            'skills_sets.*' => 'exists:title,id',
            'address' => 'required|string|max:255',
            'contact_no' => 'required|integer|min:10',
            'gender' => 'required|string|max:255',
            'date_of_birth' => 'required|date|',
            'join_date' => 'required|date|after_or_equal:date_of_birth',
            'father' => 'required|string|max:255',
            'mother' => 'required|string|max:255',
            'marital_status' => 'required',
            'pincode' => 'required|integer',
            'aadhar' => 'required|string|min:12|unique:user_metas',
            'pan' => 'required|string|min:10|unique:user_metas',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);


        if($request->hasFile('profile_pic')) {
            $file = $request->file('profile_pic');
            $imagePath = $file->store('profile_pic', 'public');   
        } else {
            $imagePath = $request->first_name;
        }
    
        $user = new User([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            //'role_id' => $request->role_id,
        ]);

        $role = Role::find($request->input('role_id'));

        if (!$role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        $user->role()->associate($role);

        $user->save();

        $user->skillSets()->sync($request->input('skill_sets'));
        
        $user->userMeta()->create([
                'address' => $request->address,
                'contact_no' => $request->contact_no,
                'gender' => $request->gender,
                'join_date' => $request->join_date,
                'date_of_birth' => $request->date_of_birth,
                'father' => $request->father,
                'mother' => $request->mother,
                'marital_status' => $request->marital_status,
                'spouse' => $request->spouse,
                'children' => $request->children,
                'pincode' => $request->pincode,
                'aadhar' => $request->aadhar,
                'pan' => $request->pan,
                'profile_pic' => $imagePath,

        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['data' => $user, 'access_token' => $token, 'token_type' => 'Bearer', ]);
            
    }

    //method for user login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password')))
        {
            return response()->json(['message' => 'Unauthorized'], 401);
                
        }

        
        //$user = User::where('email', $request['email'])->firstOrFail();

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['access_token' => $token, 'token_type' => 'Bearer', 'role' => $user->role->title]);
            
    }
    // method for user logout and delete token

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'You have successfully logged out and the token was successfully deleted'
        ];
    }
}
