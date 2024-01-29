<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    //Method for registering user
    public function register(Request $request)
    { 
        $validator = Validator::make($request->all(),[
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|max:255',
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

    if($request->hasFile('profile_pic')) {
        $imagePath = $request->file('profile_pic')->store('profile_images', 'public');
    }

    if($validator->fails()){
        return response()->json($validator->errors());       
    }

    $user = User::create([
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => $request->role,
     ]);

   /* if($request->hasfile('image')) {
        $file = $request->file('image');
        $extenstion = $file->getClientOriginalExtension();
        $filename = time().'.'.$extenstion;
        $file->move('uploads/user/'.$filename);
     }*/
    

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
            'profile_pic' => $imagePath,

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

        $token = Auth::user()->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'token_type' => 'Bearer', ]);
            
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
