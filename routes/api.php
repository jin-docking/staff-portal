<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\CompanyInfoController;
use App\Http\Controllers\ComputerAssistanceHubController;
use App\Http\Controllers\Admin\AdminLeaveController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SkillSetController;
use App\Http\Controllers\LogActivityController;


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



Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('password/reset', [ForgotPasswordController::class, 'reset'])->name('password.reset');
Route::get('holidays', [HolidayController::class, 'index']);
Route::post('/role/create', [UserRoleController::class, 'store']);

Route::get('companyinfo/', [CompanyInfoController::class, 'index']);
/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();

});*/


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/user', function(Request $request) {
        return auth()->user();
    });

    //api route for user control
    
    Route::get('user/user-count',[UserController::class, 'userCount']);
    Route::get('user/{id}',[UserController::class, 'show']);
    Route::get('/userprofile',[UserController::class, 'userProfile']);
    Route::match(['post', 'put'], '/update/{id}', [UserController::class, 'update']);

    
    //Route::get('/user/{userId}/profile-pic',[UserController::class, 'getProfilePic']);
    // API route for logout user
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('holidays/upcoming-holidays', [HolidayController::class, 'upcomingHolidays']);
    Route::get('holiday/restricted-holiday', [HolidayController::class, 'upcomingRestrictedHolidays']);

     // API route for all SkillSets
    Route::get('skillsets', [SkillSetController::class, 'index']);
});

//admin to manage staff
Route::middleware(['auth:sanctum', 'role:Admin,HR,Project Manager'])->group(function () {
    
    Route::get('user',[UserController::class, 'index']);
    Route::delete('user/{id}',[UserController::class, 'destroy']);
    
});

//user's team list
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/team', [TeamController::class, 'index']);
    Route::get('/team/show-developer/{id}', [TeamController::class, 'showDevelopers']);
    Route::get('/user-team/{id}', [TeamController::class, 'userTeam']);
    Route::get('/userteamlist', [TeamController::class, 'userTeamList']);
    Route::get('/team/team-count', [TeamController::class, 'teamCount']);
    Route::get('/team/all-team-list', [TeamController::class, 'allTeamList']);

    
});

//request for technical assistance
Route::middleware('auth:sanctum')->group(function () {

    Route::get('techassist/recent-requests',[ComputerAssistanceHubController::class, 'recentAssitanceRequests']);
    Route::post('/techassist/create/{id}',[ComputerAssistanceHubController::class, 'store']);
    Route::get('/techassists',[ComputerAssistanceHubController::class, 'index']);
    Route::get('/techassists/user-requests',[ComputerAssistanceHubController::class, 'userShow']);
    Route::get('/techassist/show/{id}',[ComputerAssistanceHubController::class, 'show']);
    Route::match(['post', 'put'],'/techassist/update/{id}',[ComputerAssistanceHubController::class, 'update']);
    Route::delete('/techassist/{id}',[ComputerAssistanceHubController::class, 'destroy']);
    Route::get('techassist/request-status',[ComputerAssistanceHubController::class, 'recentRequestStatus']);

});

//creating and managing team
Route::middleware(['auth:sanctum', 'role:Admin,HR'])->group(function () {
    
    Route::post('/team/create',[TeamController::class, 'store']);
    Route::delete('/team/{id}',[TeamController::class, 'destroy']);
    Route::put('/team/update/{id}', [TeamController::class, 'update']);
    Route::get('/allroles/{roles}', [TeamController::class, 'getUsersByRole']);

});

//creating and managing roles
Route::middleware(['auth:sanctum', 'role:Admin,HR'])->group(function () {
    Route::put('/role/update/{id}', [UserRoleController::class,'update']);
    Route::get('/role/show/{id}', [UserRoleController::class, 'show']);  
    Route::get('/roles', [UserRoleController::class, 'index']);  
    Route::delete('/role/{id}', [UserRoleController::class, 'destroy']); 
});

//leave application
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('leave/available-leave/{id}',[LeaveController::class, 'availableLeave']);
    Route::get('leave/leave-count',[LeaveController::class, 'userLeaveCount']);
    Route::post('/leave', [LeaveController::class, 'store']);
    Route::put('/leaveupdate/{id}', [LeaveController::class,'update']);
    Route::delete('/leavedelete/{id}', [LeaveController::class,'destroy']);
    Route::get('/leaveview', [LeaveController::class, 'index']);   
    Route::get('/leaveview/{id}', [LeaveController::class, 'show']);  
    Route::get('/showLeave', [LeaveController::class, 'showLeave']); 
    Route::get('leave/recent-leaves',[LeaveController::class, 'recentLeaveRequests']);  
    

});

//leave approve 
Route::middleware(['auth:sanctum','role:Admin,HR'])->group(function () {

    Route::get('/leave/view', [AdminLeaveController::class, 'index']);
    Route::get('/leave/view/{id}', [AdminLeaveController::class, 'show']);  
    Route::put('/leave/update/{id}', [AdminLeaveController::class,'update']);
    Route::delete('/leave/delete/{id}', [AdminLeaveController::class,'destroy']);
    Route::post('/leave/create/{id}', [AdminLeaveController::class, 'store']);  
    Route::get('leave/new-leaves',[AdminLeaveController::class, 'recentLeaves']);
   
});

//managing company info
Route::middleware(['auth:sanctum', 'role:Admin,HR'])->group(function () {
    
    Route::post('/companyinfo/create', [CompanyInfoController::class, 'store']);
    Route::get('/companyinfo/show/{id}', [CompanyInfoController::class, 'show']);   
    Route::match(['post', 'put'],'/companyinfo/update/{id}', [CompanyInfoController::class, 'update']);
    Route::delete('/companyinfo/delete/{id}', [CompanyInfoController::class,'destroy']);
});

//managing company holiday
Route::middleware(['auth:sanctum', 'role:Admin,HR'])->group(function () {

    Route::post('holiday/create', [HolidayController::class, 'store']);
    Route::put('holiday/update/{id}', [HolidayController::class, 'update']);
    Route::get('holiday/show/{id}', [HolidayController::class, 'show']);
    Route::delete('holiday/{id}', [HolidayController::class, 'destroy']);
});

//managing skillset
Route::middleware(['auth:sanctum', 'role:Admin,HR'])->group(function () {
    
    Route::post('skillsets/create', [SkillSetController::class, 'store']);
    Route::get('skillsets/{skillSet}', [SkillSetController::class, 'show']);
    Route::put('skillsets/update/{skillSet}', [SkillSetController::class, 'update']);
    Route::delete('skillsets/{skillSet}', [SkillSetController::class, 'destroy']);
});

// Managing location
Route::middleware('auth:sanctum')->group(function () {
    Route::get('locations', [LocationController::class, 'index']);
    Route::post('locations/create', [LocationController::class, 'store']);
    Route::get('locations/show/{id}', [LocationController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('logactivities', [LogActivityController::class, 'index']);
    Route::get('logactivities/show/{id}', [LogActivityController::class, 'show']);
});