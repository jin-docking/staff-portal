<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\Admin\AdminLeaveController;
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
    
    
    Route::get('user/{id}',[UserController::class, 'show']);
    Route::get('/userprofile',[UserController::class, 'userProfile']);
    Route::put('/update/{id}', [UserController::class, 'update']);
    // API route for logout user
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::middleware(['auth:sanctum', 'role:Admin,HR'])->group(function () {
    
    Route::get('user',[UserController::class, 'index']);
    Route::delete('user/{id}',[UserController::class, 'destroy']);
    
});

Route::middleware('auth:sanctum')->group(function () {
  
    Route::get('/team/{id}', [TeamController::class, 'show']);
    Route::get('/user-team/{id}', [TeamController::class, 'userTeam']);
    
});

Route::middleware(['auth:sanctum', 'role:Admin,HR'])->group(function () {
    
    Route::post('/team/create',[TeamController::class, 'store']);
    Route::delete('/team/{id}',[TeamController::class, 'destroy']);
    Route::put('/team/update/{id}', [TeamController::class, 'update']);
    Route::get('/team', [TeamController::class, 'index']);
    Route::get('/allroles/{roles}', [TeamController::class, 'getUsersByRole']);
    Route::put('/role/update/{id}', [UserRoleController::class,'update']);
    Route::get('/role/show/{id}', [UserRoleController::class, 'show']);  
    Route::get('/roles', [UserRoleController::class, 'index']);  
    
});


Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/leave', [LeaveController::class, 'store']);
    Route::put('/leaveupdate/{id}', [LeaveController::class,'update']);
    Route::delete('/leavedelete/{id}', [LeaveController::class,'destroy']);
    Route::get('/leaveview', [LeaveController::class, 'index']);   
    Route::get('/leaveview/{id}', [LeaveController::class, 'show']);  
    Route::get('/showLeave', [LeaveController::class, 'showLeave']);   

});

Route::middleware(['auth:sanctum','role:Admin'])->group(function () {

    Route::get('/leave/view', [AdminLeaveController::class, 'index']);
    Route::get('/leave/view/{id}', [AdminLeaveController::class, 'show']);  
    Route::put('/leave/update/{id}', [AdminLeaveController::class,'update']);
    Route::delete('/leave/delete/{id}', [AdminLeaveController::class,'destroy']);
    Route::post('/leave/create/{id}', [AdminLeaveController::class, 'store']);   
   
});


//Route::get('/roles', [UserRoleController::class, 'index']);    
Route::post('/role/create', [UserRoleController::class, 'store']);
//Route::put('/role/update/{id}', [UserRoleController::class,'update']);
//Route::get('/role/show/{id}', [UserRoleController::class, 'show']);   




