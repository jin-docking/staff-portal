<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\UserMeta;

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
    public function store(Request $request)
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
            'profile_pic' => 'required|string|max:255',
        ]);


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

        //$date = date('Y-m-d H:i:s');

        $user->userMeta()->create([
                //'user_id' => $user->id,
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
                'profile_pic' => $request->profile_pic,

        ]);
        return response()->json(['message' => 'user added'], 201);
    }
                

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::find($id);
        if (!empty($user)){
            $usermeta = $user->userMeta;
            return response()->json($usermeta);
        }
        else {
            return response()->json(['message' => 'user not found'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function userProfile()
    {   
        $user = Auth::user();
        $userMeta = $user->userMeta;
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        
        if (User::where('id', $id)->exists())
        {
            $user = User::findOrFail($id);

            $user->update([
                'first_name' => is_null($request->first_name) ? $user->first_name : $request->first_name,
                'last_name' => is_null($request->last_name) ? $user->last_name : $request->last_name,
                'email' => is_null($request->email) ? $user->email : $request->email,
                'password' => is_null($request->password) ? $user->password : Hash::make($request->password),
                'role' => is_null($request->role) ? $user->role : $request->role,
            ]);
            
            $user->userMeta()->update([
                'address' => is_null($request->address) ? $user->userMeta->address : $request->address,
                'designation' => is_null($request->designation) ? $user->userMeta->designation : $request->designation,
                'gender' => is_null($request->gender) ? $user->userMeta->gender : $request->gender,
                'join_date' => is_null($request->join_date) ? $user->userMeta->join_date : $request->join_date,
                'date_of_birth' => is_null($request->date_of_birth) ? $user->userMeta->date_of_birth : $request->date_of_birth,
                'father' => is_null($request->father) ? $user->userMeta->father : $request->father,
                'mother' => is_null($request->mother) ? $user->userMeta->mother : $request->mother,
                'spouse' => is_null($request->spouse) ? $user->userMeta->spouse : $request->spouse,
                'children' => is_null($request->children) ? $user->userMeta->children : $request->children,
                'pincode' => is_null($request->pincode) ? $user->userMeta->pincode : $request->pincode,
                'aadhar' => is_null($request->aadhar) ? $user->userMeta->aadhar : $request->aadhar,
                'pan' => is_null($request->pan) ? $user->userMeta->pan : $request->pan,
                'profile_pic' =>is_null($request->profile_pic) ? $user->userMeta->profile_pic : $request->profile_pic,
        ]);

        return response()->json(['message' => 'user updated'], 200);

        } else {

            return response()->json(['message' => 'user not found'], 404);
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
