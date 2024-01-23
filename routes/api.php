<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeamController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post("/register",[AuthController::class, 'register']);
Route::post("/login",[AuthController::class, 'login']);


/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();

});*/

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/user', function(Request $request) {
        return auth()->user();
    });
    //api route for staff management
    
    Route::get('user',[UserController::class, 'index']);
    Route::get('user/{id}',[UserController::class, 'show']);
    Route::post('user',[UserController::class, 'store']);
    Route::delete('user/{id}',[UserController::class, 'destroy']);
    Route::put('/update/{id}', [UserController::class, 'update']);
   

    // API route for logout user
    Route::post('/logout', [AuthController::class, 'logout']);
});
/*Route::post('/store',[App\Http\Controllers\Api\ManageStaffProfileController::class, 'store']);
Route::get('/index',[App\Http\Controllers\Api\ManageStaffProfileController::class, 'index']);*/
/*Route::apiResource('/team', TeamController::class);*/
/*Route::post('team',[TeamController::class, 'store']);
Route::post('team/{id}',[TeamController::class, 'assignUser']);
Route::get('team/{id}',[TeamController::class, 'show']);
Route::delete('team/{id}',[TeamController::class, 'destroy']);
Route::put('team/{id}',[TeamController::class, 'update']);*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/team/{id}', [TeamController::class, 'show']);
    Route::get('/team', [TeamController::class, 'index']);
    Route::post('/team/{teamId}/assign-user', [TeamController::class, 'assignUser']);
    Route::post('/team',[TeamController::class, 'store']);
});


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/leave', [LeaveController::class, 'store']);
    Route::put('/leaveupdate/{id}', [LeaveController::class,'update']);
    Route::delete('/leavedelete/{id}', [LeaveController::class,'destroy']);
    Route::get('/leaveshow/{id}', [LeaveController::class, 'show']);
});