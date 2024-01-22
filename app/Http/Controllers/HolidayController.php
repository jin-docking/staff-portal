<?php

namespace App\Http\Controllers;

use App\Models\holiday;
use App\Http\Requests\StoreholidayRequest;
use App\Http\Requests\UpdateholidayRequest;
use App\Http\Controllers\Controller;

class HolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreholidayRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(holiday $holiday)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(holiday $holiday)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateholidayRequest $request, holiday $holiday)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(holiday $holiday)
    {
        //
    }
}
