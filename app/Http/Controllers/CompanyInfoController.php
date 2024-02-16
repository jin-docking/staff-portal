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
        $info = CompanyInfo::all();

        return response()->json($info);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'logo' => 'required|string',
            'email' => 'required|string|email',
            'address' => 'required|string',
            'contact_no' => 'required'
        ]);

        $info = CompanyInfo::create([
            'title' => $request->title,
            'description' => $request->description,
            'logo' => $request->logo,
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
        $info = CompanyInfo::findOrFail($id);

        return response()->json(['data' => $info]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        
        $info = CompanyInfo::findOrFail($id);

        $info->delete();

        return response()->json(['message' => 'information has been deleted']);
    }
}
