<?php
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api2Controller;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Authentication  
// Route::get('show',[Api2Controller::class,'show']);

//////////////////////////////////  Cutomers //////////////////////////////////////////////////////////
Route::POST('signup_customer',[Api2Controller::class,'signup_customers']);
Route::POST('login_customers', [Api2Controller::class, 'login_customers']);

//////////////////////////////////  Password Rest for Customers ///////////////////////////////////////////////
Route::POST('forget_password', [Api2Controller::class, 'forget_password']);
Route::PUT('reset_password', [Api2Controller::class, 'reset_password']);

/////////////////////////////////  Jobs  /////////////////////////////////////////////////
Route::POST('create_jobs', [Api2Controller::class, 'create_jobs']);
Route::POST('calculate_payment', [Api2Controller::class, 'calculate_payment']);
Route::GET('show_jobs', [Api2Controller::class, 'show_jobs']);
Route::GET('accepted_jobs', [Api2Controller::class, 'accepted_jobs']);
Route::PUT('update_accepted_jobs', [Api2Controller::class, 'update_accepted_jobs']);
Route::GET('show_accepted_jobs', [Api2Controller::class, 'show_accepted_jobs']);
Route::GET('show_jobsbyId', [Api2Controller::class, 'show_jobsbyId']);
Route::POST('delete_job', [Api2Controller::class, 'delete_job']);

//////////////////////////////  Employee  ////////////////////////////////////////////////////////
Route::POST('signup_employee',[Api2Controller::class,'signup_employee']);
Route::POST('login_employees',[Api2Controller::class,'login_employees']);


/////////////////////////// Password Reset for Employees ///////////////////////////////////////
Route::POST('forget_password_employee', [Api2Controller::class, 'forget_password_employee']);
Route::PUT('reset_password_employee', [Api2Controller::class, 'reset_password_employee']);


/////////////////////////// Chat Connection and Message ///////////////////////////////////////
Route::POST('enablechat', [Api2Controller::class, 'enablechat']);
