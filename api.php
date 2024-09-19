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

//////////////////////////////////  Cutomers //////////////////////////////////////////////////////////
Route::POST('signup_customer',[Api2Controller::class,'signup_customers']);
Route::POST('login_customers', [Api2Controller::class, 'login_customers']);

//////////////////////////////////  Password Rest for Customers ///////////////////////////////////////////////
Route::POST('forget_passwordforcust', [Api2Controller::class, 'forget_passwordforcust']);
Route::PUT('reset_passwordforcust', [Api2Controller::class, 'reset_passwordforcust']);

/////////////////////////////////  Jobs | Customer Side /////////////////////////////////////////////////
Route::POST('create_jobs', [Api2Controller::class, 'create_jobs']);
Route::POST('calculate_payment', [Api2Controller::class, 'calculate_payment']);
Route::GET('show_customer_jobs', [Api2Controller::class, 'show_customer_jobs']);

Route::GET('show_OnGoing_jobs', [Api2Controller::class, 'show_OnGoing_jobs']);
Route::GET('show_completed_jobs', [Api2Controller::class, 'show_completed_jobs']);

/////////////////////////////////  Change Password for Customer & delete account ///////////////////////////////////////////
Route::PUT('change_customer_password', [Api2Controller::class, 'change_customer_password']);
Route::POST('delete_customer_account', [Api2Controller::class, 'delete_customer_account']);


/////////////////////////////////  Jobs | Employee Side /////////////////////////////////////////////////
Route::GET('show_pending_jobs', [Api2Controller::class, 'show_pending_jobs']);

Route::GET('accepted_jobs', [Api2Controller::class, 'accepted_jobs']);
Route::PUT('edit_jobs', [Api2Controller::class, 'edit_jobs']);
Route::GET('show_employee_jobs', [Api2Controller::class, 'show_employee_jobs']);

Route::POST('started_jobs', [Api2Controller::class, 'started_jobs']);
Route::POST('show_started_jobs', [Api2Controller::class, 'show_started_jobs']);

Route::POST('completed_jobs', [Api2Controller::class, 'completed_jobs']);
Route::POST('cancelled_jobs', [Api2Controller::class, 'cancelled_jobs']);

Route::POST('delete_job', [Api2Controller::class, 'delete_job']);

//////////////////////////////  Employee  ////////////////////////////////////////////////////////
Route::POST('signup_employee',[Api2Controller::class,'signup_employee']);
Route::POST('login_employees',[Api2Controller::class,'login_employees']);


/////////////////////////// Password Reset for Employees ///////////////////////////////////////
Route::POST('forget_passwordforemp', [Api2Controller::class, 'forget_passwordforemp']);
Route::PUT('reset_passwordforemp', [Api2Controller::class, 'reset_passwordforemp']);


/////////////////////////// Chat Connection and Message ///////////////////////////////////////
Route::POST('enablechat', [Api2Controller::class, 'enablechat']);


//////////////////////////  Job Rating and Reviews ////////////////////////////////////////
Route::POST('job_rating', [Api2Controller::class, 'job_rating']);
Route::GET('show_job_rating', [Api2Controller::class, 'show_job_rating']);


//////////////////////////  Transaction ///////////////////////////////////////////////////
Route::POST('transferamount', [Api2Controller::class, 'transferamount']);


/////////////////////////////////  Change Password for Customer & delete account ///////////////////////////////////////////
Route::PUT('change_employee_password', [Api2Controller::class, 'change_employee_password']);
Route::POST('delete_employee_account', [Api2Controller::class, 'delete_employee_account']);
