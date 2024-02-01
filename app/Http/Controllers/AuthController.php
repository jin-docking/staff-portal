<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
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
            'address' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'gender' => 'required|string|max:255',
            'join_date' => 'required',
            'date_of_birth' => 'required',
            'father' => 'required|string|max:255',
            'mother' => 'required|string|max:255',
            'pincode' => 'required|integer',
            'aadhar' => 'required|string|max:255',
            'pan' => 'required|string|max:255',
            'profile_pic' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);
    
    /*if($request->hasFile('profile_pic')) {
        $imagePath = $request->file('profile_pic')->store('profile_images', 'public');
    }*/

    if ($request->hasFile('profile_pic') && $request->file('profile_pic')->isValid()) {
        $image = $request->file('profile_pic');
        $imageName = time() . '.' . $image->extension();
        $image->move(public_path('images'), $imageName);
    } else {
        return response()->json(['error' => 'Invalid or missing profile picture'], 422);
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
    $user->userMeta()->create([
            
            'address' => $request->address,
            'designation' => $request->designation,
            'gender' => $request->gender,
            'join_date' => $request->join_date,
            'date_of_birth' => $request->date_of_birth,
            'father' => $request->father,
            'mother' => $request->mother,
            'spouse' => $request->spouse,
            'children' => $request->children,
            'pincode' => $request->pincode,
            'aadhar' => $request->aadhar,
            'pan' => $request->pan,
            'profile_pic' => $imageName,

    ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['data' => $user,'access_token' => $token, 'token_type' => 'Bearer', ]);
            
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
