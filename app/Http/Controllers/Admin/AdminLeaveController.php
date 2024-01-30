<?php

namespace App\Http\Controllers\Admin;

use App\Models\Leave;
use App\Http\Controllers\Controller;

class AdminLeaveController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index1()
    {
             
        $leaves = Leave::all();

        return response()->json($leaves);
    }  
}
