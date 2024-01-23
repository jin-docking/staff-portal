<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
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
    Route::get('/userprofile',[UserController::class, 'userProfile']);
    Route::post('user',[UserController::class, 'store']);
    Route::delete('user/{id}',[UserController::class, 'destroy']);
    Route::put('/update/{id}', [UserController::class, 'update']);
   

    // API route for logout user
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/team/{id}', [TeamController::class, 'show']);
    Route::get('/team', [TeamController::class, 'index']);
    Route::post('/team/{teamId}/assign-user', [TeamController::class, 'assignUser']);
    Route::post('/team',[TeamController::class, 'store']);
    
});