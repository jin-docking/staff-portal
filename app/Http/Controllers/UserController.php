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
       
        $user = User::with('userMeta')->orderBy('first_name')->get();

        return response()->json($user);

    }

    /**
     * Store a newly created resource in storage.
     */
   /* public function store(Request $request)
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
    }*/
                

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::find($id);
        if (!empty($user)){
            $usermeta = $user->userMeta;
            return response()->json($user);
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
        
       
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->id == $id || $user->role == 'Admin') {
            
            if (User::where('id', $id)->exists())
            {
                $user = User::findOrFail($id);

                $user->update([
                    'first_name' => $request->input('first_name', $user->first_name),
                    'last_name' => $request->input('last_name', $user->last_name),
                    'email' => $request->input('email', $user->email),
                    'password' => $request->has('password') ? Hash::make($request->input('password')) : $user->password,
                    'role' => $request->input('role', $user->role),
                ]);
            
                if ($request->hasFile('profile_pic')) {
                    $imagePath = $request->file('profile_pic')->store('profile_images', 'public');
                } else {
                    $imagePath = $user->userMeta->profile_pic;
                }
            
                $user->userMeta()->update([
                    'address' => $request->input('address', $user->userMeta->address),
                    'designation' => $request->input('designation', $user->userMeta->designation),
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
