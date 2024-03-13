<?php

namespace App\Http\Controllers;

use App\Models\TechAssist;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;


class ComputerAssistanceHubController extends Controller
{
    public function index()
    {
        $hubs = TechAssist::all();

        $data = [];
        foreach ($hubs as $hub) {
            $userData = [
                'name' => $hub->user->first_name .' '. $hub->user->last_name,
                'email' => $hub->user->email
            ];

            $data[] = [
                'hub' => $hub,
                'user' => $userData
            ];
    }

    return response()->json(['data' => $data]);
        /*return response()->json(['data' => $hub,
                    'user' => [        
                    'name' => $hub->user->first_name .' '. $hub->user->last_name,
                    'email' => $hub->user->email]]);*/
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'invoice' => 'nullable|mime:pdf|max:2048',
        ]);   
        
        if($request->hasFile('invoice')){
            $invoicePath = $request->file('invoice')->store('invoices', 'public');
        } else {
            $invoicePath = 'invoice';
        }

        $hub =TechAssist::create([
            'user_id' => $user->id,                                    
            'title' => $request->title,
            'status' => $request->input('status', 'pending'),
            'description' => $request->description,
            'invoice' => $invoicePath,
        ]);
           
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

        if ($user->id != $hub->user_id) {
             return response()->json(['error' => 'You do not have permission to update this request.'], 403);
        }             
         
        $request->validate([
            'title' => 'required|string',
            'description' => 'Required|string',
            'invoice' => 'nullable|mimes:pdf|max:2048'
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

        return response()->json(['message' => 'Request updated successfully', 'data' => $hub], 204);
    } 


    public function show($id)
    {
        if (!TechAssist::where('id', $id)->exists()){
            return response()->json(['message' => 'request does not exists'], 404);

        }

        $hub = TechAssist::findorFail($id);

        return response()->json(['data' => $hub,
                    'user' => [        
                    'name' => $hub->user->first_name .' '. $hub->user->last_name,
                    'email' => $hub->user->email]]);
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
}
