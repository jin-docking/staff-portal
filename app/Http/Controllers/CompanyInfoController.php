<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\CompanyInfo;
use Illuminate\Http\Request;

class CompanyInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $infos = CompanyInfo::all();

        foreach ($infos as $info){
            $info->logo = asset('storage/' . $info->logo);
        }

        return response()->json($infos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'logo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'email' => 'required|string|email',
            'address' => 'required|string',
            'contact_no' => 'required'
        ]);

        if($request->hasFile('logo')) {
            
            $file = $request->file('logo');

            if ($file->isValid()) {
                
                $logoPath = $file->store('logo', 'public');

            } else {
                
                $error = $file->getError();     
                
                return response()->json($error);
            }

        }

        $info = CompanyInfo::create([
            'title' => $request->title,
            'description' => $request->description,
            'logo' => $logoPath,
            'email' => $request->email,
            'address' => $request->address,
            'contact_no' => $request->contact_no,
        ]);

        return response()->json(['message' => 'Company Info created', 'data' => $info], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        
        if (!CompanyInfo::where('id', $id)->exists()){
            return response()->json(['message' => 'Company info does not exists'], 404);
        
        }

        $info = CompanyInfo::findOrFail($id);

        $info->logo = asset('storage/' . $info->logo);

        return response()->json(['data' => $info]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!CompanyInfo::where('id', $id)->exists()){
            return response()->json(['message' => 'information does not exists'], 404);

        }        

        $info = CompanyInfo::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            //'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'email' => 'required|string|email',
            'address' => 'required|string',
            'contact_no' => 'required'
        ]);

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {

            $logoPath = $request->file('logo')->store('logo', 'public');

        } else {
            $logoPath = $info->logo;
        }

        $info->update([
            'title' => $request->input('title', $info->title),
            'description' => $request->input('description', $info->description),
            'logo' => $logoPath,
            'email' => $request->input('email', $info->email),
            'address' => $request->input('address', $info->address),
            'contact_no' => $request->input('contact_no', $info->contact_no),
        ]);   

        return response()->json(['message' => 'information updated successfully', 'data' => $info]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!CompanyInfo::where('id', $id)->exists()){
            return response()->json(['message' => 'information does not exists'], 404);

        }
        
        $info = CompanyInfo::findOrFail($id);

        $info->delete();

        return response()->json(['message' => 'information has been deleted']);
    }
}
