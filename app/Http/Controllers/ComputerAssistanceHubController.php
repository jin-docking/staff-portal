<?php

namespace App\Http\Controllers;

use App\Models\TechAssist;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\LeaveNotificationMail;
use App\Mail\TechRequestMail;

class ComputerAssistanceHubController extends Controller
{
    public function index()
    {
        $hubs = TechAssist::all();

        $data = [];
        foreach ($hubs as $hub) {
            $creatorName = $hub->created_by === $hub->user_id ? 'Self' : User::where('id', $hub->created_by)->value('first_name').' '.User::where('id', $hub->created_by)->value('last_name');

            $userData = [
                'name' => $hub->user->first_name .' '. $hub->user->last_name,
                'email' => $hub->user->email,
                'creator_name' => $creatorName
            ];

            $data[] = [
                'hub' => $hub,
                'user' => $userData
            ];
        }

    return response()->json(['data' => $data]);

    }

    public function userShow()
    {
        $user = Auth::user();

        $hub = TechAssist::where('user_id', $user->id)->get();

        return response()->json(['data' => $hub]);

    }
    
    public function store(Request $request, $id)
    {
        $authUser = Auth::user();

        $user = User::findOrFail($id);

        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            //'invoice' => 'nullable|mimes:pdf|max:2048',
        ]);   


        
        if($request->hasFile('invoice')){
            $invoicePath = $request->file('invoice')->store('invoices', 'public');
        } else {
            $invoicePath = null;
        }

        $hub = TechAssist::create([
            'user_id' => $user->id,
            'created_by' => $authUser->id,                                    
            'title' => $request->title,
            'status' => $request->input('status', 'pending'),
            'description' => $request->description,
            'invoice' => $invoicePath,
        ]);

        $role = Role::where('title', 'Admin')->first();
        $admin = null;
        if ($role) {
            $admin = User::where('role_id', $role->id)->first();
        }

        if ($admin) {
            Mail::to($admin->email)->send(new TechRequestMail($hub, $user, 'request'));
        }
           
        return response()->json(['message' => 'Request for assistance has been successful',    
            'data' => [
                'title' => $hub,
                'creator_name' => $user->first_name ." ". $user->last_name,
            ],
        ], 200);
        
    }

    public function update(Request $request, $id)
     {        
        
        if (!TechAssist::where('id', $id)->exists()){
            return response()->json(['message' => 'request does not exists'], 404);

        }
        
        $hub = TechAssist::findorFail($id);

        $user = Auth::user();

        /*if ($user->id != $hub->user_id) {
             return response()->json(['error' => 'You do not have permission to update this request.'], 403);
        } */            
         
        $request->validate([
            'title' => 'required|string',
            'description' => 'Required|string',
            //'invoice' => 'nullable|mimes:pdf|max:2048'
        ]);     

        if($request->hasFile('invoice')){
            $invoicePath = $request->file('invoice')->store('invoices', 'public');
        
        } else {
            $invoicePath = $hub->invoice;
            
        }
        
        $hub->update([
            'title' => $request->input('title', $hub->title),
            'description' => $request->input('description', $hub->description),
            'status' => $request->input('status', $hub->status),
            'invoice' => $invoicePath,  
        ]);   
        
        $role = Role::where('title', 'Admin')->first();
        $admin = null;
        if ($role) {
            $admin = User::where('role_id', $role->id)->first();
        }

        if ($admin) {
            Mail::to($user->email)->send(new TechRequestMail($hub, $admin, 'request'));
        }

        return response()->json(['message' => 'Request updated successfully', 'data' => $hub], 204);
    } 


    public function show($id)
    {
        if (!TechAssist::where('id', $id)->exists()){
            return response()->json(['message' => 'request does not exists'], 404);

        }

        $hub = TechAssist::findorFail($id);

        
        if ($hub->invoice == null) {
            $userData = [
                'name' => $hub->user->first_name .' '. $hub->user->last_name,
                'email' => $hub->user->email,
                'creator_name' => $hub->created_by === $hub->user_id ? 'Self' : User::where('id', $hub->created_by)->value('first_name').' '.User::where('id', $hub->created_by)->value('last_name'),
            ];

            return response()->json(['data' => $hub, 'user' => $userData]);
        } else {
            $hub->invoice = asset('storage/' . $hub->invoice);
        }

        $userData = [
            'name' => $hub->user->first_name .' '. $hub->user->last_name,
            'email' => $hub->user->email,
            'creator_name' => $hub->created_by === $hub->user_id ? 'Self' : User::where('id', $hub->created_by)->value('first_name').' '.User::where('id', $hub->created_by)->value('last_name'),
        ];

        return response()->json(['data' => $hub, 'user' => $userData]);
    }


    public function destroy($id)
    {
    
        if (!TechAssist::where('id', $id)->exists()){
            return response()->json(['message' => 'request does not exists'], 404);

        }

        $hub = TechAssist::findorFail($id);

        $hub->delete();

        return response()->json(['message' => 'request has deleted'], 202);
    }

    public function recentAssitanceRequests()
    {
        //$week = now()->subWeek();

        $recentRequest = TechAssist::where('status', '=', 'pending')
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();

        $data = [];

        foreach ($recentRequest as $recent) {
            $userData = [
                'name' => $recent->user->first_name .' '. $recent->user->last_name,
                'email' => $recent->user->email
            ];
            $data[] = [
                'title' => $recent->title,
                'status' => $recent->status,
                'user' => $userData
            ];
        }

        return response()->json(['data' => $data], 200);
    }

    public function recentRequestStatus()
    {

        $user = Auth::user();
            
        $assistanceRequest = TechAssist::where('user_id', $user->id)->orderBy('created_at', 'DESC')->limit(3)->get();
            
        return response()->json(['data' => $assistanceRequest], 200);
        
    }
    
}
