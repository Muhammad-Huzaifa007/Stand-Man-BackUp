<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class Api2Controller extends Controller
{
    // //////////////////////////////////////    Sign Up Api ///////////////////////////////////
    public function signup_customers(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'image' => 'required|string', // Base64 validation
            'phone' => 'required|string',
            'user_type' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 401);
        }
        
        // Check if email already exists
        $emailExists = DB::table('huzaifa_users')->where('email', $request->email)->exists();
        
        if ($emailExists) {
            return response()->json(['message' => 'Email already exists'], 409); // 409 Conflict
        }
    
        // Handling the Image
        $encodedImage = $request->input('image');
        $filename = $this->decodeCustomerImage($encodedImage);
        
        if (!$filename) {
            return response()->json(['message' => 'Invalid image format'], 400);
        }
        
        // Insert data into the database
        $user = DB::table('huzaifa_users')->insert([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'user_type' => $request->user_type,
            'image' => $filename, // Store filename instead of base64
            'wallet_balance' => 5000
        ]);
        
        // Return the response
        if ($user) {
            return response()->json([
                'status' => 'Success',
                'message' => 'User Created Successfully!',
            ], 200);
        } else {
            return response()->json([
                'message' => 'User could not be created',
                'status' => 'Error'
            ], 400);
        }
    }
    
    // Function to decode base64 image URL and save the image as customer_image
    private function decodeCustomerImage($base64String)
    {
        // Extract base64 data
        $base64Data = $this->extractBase64Data($base64String);
        
        if (!$base64Data) {
            return false;
        }
        
        // Use 'customer_image' as the filename without prefix
        $filename = 'customer_image.png'; // Fixed name for customer image
        
        // Ensure the public/uploads directory exists
        $uploadPath = public_path('uploads');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
    
        // Save the image in public/uploads
        $filePath = $uploadPath . '/' . $filename;
        file_put_contents($filePath, base64_decode($base64Data));
    
        // Return the filename for storing in the database
        return 'uploads/' . $filename; // Return path to store in DB
    }
    
    // Function to extract base64 image data
    private function extractBase64Data($base64String)
    {
        return preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
    }
    

    // //////////////////////////////////////  Customer Login Api //////////////////////////////
    public function login_customers(Request $request){
        // Custom Validation
        if(empty($request->email) || empty($request->password)){
            return response()->json([
        'status' => 'error',
        'message' => 'All Fields are Required' 
    ], 200);
    }

    // Retrieving the User by Email
    $user = DB::table('huzaifa_users')->where('email', $request->email)->first();
    
    // Checking If the user exists and the password is correct
    if($user && Hash::check($request->password, $user->password)){
        return response()->json([
            'status' => 'success',
            'Message' => 'User Logged in Successfully! ',
            'data' => $user
        ], 200);
    }else{
        return response()->json([
            'status' => 'error',
            'message' => 'Email and Password does not Match!'
        ], 400);
    }
    
    }
// //////////////////////////////////////  Forget Password for Customers Api /////////////////////////////
    public function forget_passwordforcust(Request $request)
    {
        // Storing the given email in $email variable
        $email = $request->input('email');

        // Checking if the Customer exists in the huzaifa_users table
        $customer = DB::table('huzaifa_users')->where('email', $email)->first();

        if (!$customer) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Customer does not exist'
            ], 404);
        }

        // Generating a 4-digit random number as OTP
        $otp = rand(1000, 9999);

        // Storing or updating the OTP in huzaifa_users_otp table
        DB::table('huzaifa_users_otp')->updateOrInsert(
            ['user_email' => $email],
            ['otp' => $otp]
        );
        
        // Sending OTP to customer's email
        $this->dispatchOtpEmail($email, $otp);

        return response()->json([
            'status' => 'Success',
            'message' => 'OTP has been sent to your Email',
            'OTP' => $otp,
        ]);
    }

    // Function to send OTP email
    private function dispatchOtpEmail($email, $otp)
    {
        $subject = "Your Password Reset OTP";
        $message = "Your OTP for resetting your password is: $otp";

        // Sending the OTP email using Laravel's Mail facade
        Mail::raw($message, function ($mail) use ($email, $subject) {
            $mail->to($email)
                ->subject($subject);
        });
    }

// //////////////////////////////////////  Update Password for Customer Api /////////////////////////////////////
    public function reset_passwordforcust(Request $request){
    $email = $request->input('email');
    $newpassword = $request->input('password');

    // Checking if the user exists in the database table
    $user = DB::table('huzaifa_users')->where('email', $email)->first();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User does not exist'
        ], 400);
    }

    // Hashing the new password
    $hashpassword = Hash::make($newpassword);
    
    // Update the password in the database
    DB::table('huzaifa_users')
        ->where('email', $email)
        ->update(['password' => $hashpassword]);

    return response()->json([
        'status' => 'success',
        'message' => 'Password Updated Successfully'
    ], 200);
    }
 // //////////////////////////////////////    Create Jobs by Customer Api ////////////////////////////////////////
    public function create_jobs(Request $request)
    {
    // Validation
    $validator = Validator::make($request->all(), [
        'customer_id' => 'required|int',
        'image' => 'required|string', // Base64 validation
        'name' => 'required|string',
        'job_date' => 'required|string',
        'start_time' => 'required|string',
        'end_time' => 'required|string',
        'special_instructions' => 'required|string',
        'location' => 'required|string',
        'amount' => 'required|numeric|min:21',  // Minimum amount should be 21
        'service_charges' => 'required|numeric',
        'tax' => 'required|numeric',
        'total_price' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 401);
    }

    // Handling the Image
    $encodedImage = $request->input('image');
    $filename = $this->decodeBase64ImageForJob($encodedImage);

    if (!$filename) {
        return response()->json(['message' => 'Invalid image format'], 400);
    }

    // Inserting data into the database with default status 'pending'
    $jobId = DB::table('huzaifa_create_jobs')->insertGetId([
        'customer_id' => $request->customer_id,
        'image' => $filename,
        'name' => $request->name,
        'job_date' => $request->job_date,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
        'special_instructions' => $request->special_instructions,
        'location' => $request->location,
        'amount' => $request->amount,
        'service_charges' => $request->service_charges,
        'tax' => $request->tax,
        'total_price' => $request->total_price,
        'status' => 'Pending',
        'payment_status' => 'Not Paid',
    ]);

    // Return the response
    if ($jobId) {
        // Retrieve the job details from the database
        $job = DB::table('huzaifa_create_jobs')->where('id', $jobId)->first();

        return response()->json([
            'status' => 'Success',
            'message' => 'Job Created Successfully!',
            'data' => $job,
        ], 200);
    } else {
        return response()->json([
            'status' => 'Error',
            'message' => 'Job could not be created'
        ], 400);
    }
    }

// Function to decode base64 image for job creation and save it in public/uploads folder
    private function decodeBase64ImageForJob($base64String)
    {
    // Extract base64 data
    $base64Data = $this->getBase64ImageContent($base64String);
    
    if (!$base64Data) {
        return false;
    }
    
    // Generate a unique filename
    $filename = 'uploads/job_image_' . time() . '.png';
    
    // Save the image in the public/uploads directory
    $filePath = public_path($filename);
    file_put_contents($filePath, base64_decode($base64Data));
    
    return $filename;
    }

// Function to extract base64 image content
    private function getBase64ImageContent($base64String)
    {
    // Check if the string is in base64 format and remove the metadata part
    return preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
    }

 
    
    // //////////////////////////////////////    Job Estimated Payment Api /////////////////////////////////
    public function calculate_payment(Request $request)
    {
    // Validation
    $validator = Validator::make($request->all(), [
        'start_time' => 'required|string',
        'end_time' => 'required|string',
    ]);
    
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 401);
    }

    // Calculate the amount based on start_time and end_time
    $startTime = strtotime($request->start_time);
    $endTime = strtotime($request->end_time);
    $totalMinutes = ($endTime - $startTime) / 60;

    $hourlyRate = 21; // $21 per hour
    $minuteRate = 0.35; // 0.35¢ per minute

    // Ensure minimum amount is $21
    $calculatedAmount = max(($hourlyRate * floor($totalMinutes / 60)) + ($minuteRate * ($totalMinutes % 60)), 21);

    // Calculate service charges (10%) and tax (13%)
    $calculatedServiceCharges = $calculatedAmount * 0.10;
    $calculatedTax = $calculatedAmount * 0.13;
    $calculatedTotalPrice = $calculatedAmount + $calculatedServiceCharges + $calculatedTax;

    // Return the calculated values
    return response()->json([
        'status' => 'Success',
        'message' => 'Payment Calculated Successfully!',
        'calculated_amount' => $calculatedAmount,
        'calculated_service_charges' => $calculatedServiceCharges,
        'calculated_tax' => $calculatedTax,
        'calculated_total_price' => $calculatedTotalPrice
    ], 200);
    }
// /////////////////////////  Displaying Created Jobs by Customer Id Api ///////////////////
    public function show_customer_jobs(Request $request)
    {
    // Validation
    $validator = Validator::make($request->all(), [
        'customer_id' => 'required|integer', 
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => $validator->errors(),
        ], 400);
    }

    // Getting Customer ID from Request
    $customerId = $request->input('customer_id');

    // Check if the customer_id does not exist
    $existingCustomer = DB::table('huzaifa_create_jobs')->where('customer_id', $customerId)->exists();

    if (!$existingCustomer) {
        // If customer_id does not exist, return an error
        return response()->json([
            'status' => 'error',
            'message' => 'Customer ID does not exist.',
        ], 400); 
    }

    // Proceed if the customer_id exists
    $createdJobs = DB::table('huzaifa_create_jobs')->where('customer_id', $customerId)->get();

    return response()->json([
        'status' => 'success',
        'data' => $createdJobs,
    ], 200);
    }
 // //////////////////////////////////////    Show On Going Jobs Api | Customer side  //////////////////////////
    public function show_OnGoing_jobs(Request $request){
    // Get the customer_id from the request
    $cid = $request->input('customer_id');

    // Check if the customer exists in the huzaifa_create_jobs table
    $customer = DB::table('huzaifa_create_jobs')->where('customer_id', $cid)->first();

    // If the customer does not exist, return an error response
    if(!$customer){
        return response()->json([
            'status' => 'error',
            'message' => 'Customer ID does not exist'
        ], 400);
    }

    // Fetch the jobs for the customer with 'On Going' status
    $jobs = DB::table('huzaifa_create_jobs')
        ->where('customer_id', $cid)
        ->where('status', 'On Going')
        ->get();

    // Check if no jobs are found with 'On Going' status
    if($jobs->isEmpty()){
        return response()->json([
            'status' => 'error',
            'message' => 'Customer has no ongoing jobs'
        ], 404);
    }

    // Return the jobs in a success response
    return response()->json([
        'status' => 'success',
        'jobs' => $jobs
    ], 200);
    }
   // //////////////////////////////////////  Edit Accepted Jobs Api //////////////////////////////
    public function edit_jobs(Request $request) {
    $request->validate([
        'job_id' => 'required|int',  // Validate job_id
        'customer_id' => 'required|int',  // Validate customer_id
        'image' => 'required|string', // Base64 validation
        'name' => 'required|string',
        'job_date' => 'required|string',
        'start_time' => 'required|string',
        'end_time' => 'required|string',
        'special_instructions' => 'required|string',
        'location' => 'required|string',
        'amount' => 'required|numeric|min:21',  // Minimum amount should be 21
        'service_charges' => 'required|numeric',
        'tax' => 'required|numeric',
        'total_price' => 'required|numeric',
    ]);

    // Check if the job exists in huzaifa_create_jobs table
    $job = DB::table('huzaifa_create_jobs')->where('id', $request->job_id)->first();
    
    if (!$job) {
        return response()->json([
            'status' => 'error',
            'message' => 'Job not found, Job Id is incorrect',
        ], 400);
    }

    // Verify if the customer_id exists in the customers table (assuming you have a customers table)
    $customer = DB::table('huzaifa_create_jobs')->where('customer_id', $request->customer_id)->first();
    
    if (!$customer) {
        return response()->json([
            'status' => 'error',
            'message' => 'Customer not found, Customer Id is incorrect',
        ], 400);
    }

    // Update the fields in huzaifa_create_jobs table
    DB::table('huzaifa_create_jobs')
    ->where('id', $request->job_id)
    ->update([
        'customer_id' => $request->input('customer_id'),
        'image' => $request->input('image'),
        'name' => $request->input('name'),
        'job_date' => $request->input('job_date'),
        'start_time' => $request->input('start_time'),
        'end_time' => $request->input('end_time'),
        'special_instructions' => $request->input('special_instructions'),
        'location' => $request->input('location'),
        'amount' => $request->input('amount'), // Taken from payload
        'service_charges' => $request->input('service_charges'), // Taken from payload
        'tax' => $request->input('tax'),
        'total_price' => $request->input('total_price'),
    ]);

    // Fetch the updated job details
    $updatedJob = DB::table('huzaifa_create_jobs')->where('id', $request->job_id)->first();

    return response()->json([
        'status' => 'Success',
        'message' => 'Job updated successfully!',
        'Updated Job' => $updatedJob
    ], 200);
    }
// ///////////////////////////  Delete Job Api ///////////////////////////////////////////
    public function delete_job(Request $request){
    $request->validate([
        'job_id' => 'required|integer'
    ]);

    // Finding the Job in huzaifa_create_jobs table by it's Id
    $job = DB::table('huzaifa_create_jobs')->where('id', $request->job_id)->first();

    if(!$job){
    return response()->json([
        'status' => 'error',
        'message' => 'Job Id not found',
    ],400);
    }
    DB::table('huzaifa_create_jobs')->where('id', $request->job_id)->delete();

    return response()->json([
    'status' => 'success',
    'message' => 'Job deleted!',
    ],200);

    }
//////////////////////////////////// Show Complted Jobs /////////////////////////////////////
    public function show_completed_jobs(Request $request) {  
    $cid = trim($request->input('customer_id'));

    // Check if the customer exists in the huzaifa_create_jobs table
    $customerExists = DB::table('huzaifa_create_jobs')->where('customer_id', $cid)->exists();

    // If the customer does not exist, return an error response
    if (!$customerExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Customer ID does not exist'
        ], 400);
    }

    // Fetch the jobs for the customer with 'Completed' status
    $jobs = DB::table('huzaifa_create_jobs')
        ->where('customer_id', $cid)
        ->where('status', 'Completed') 
        ->get();

    // Check if no jobs are found with 'Completed' status
    if ($jobs->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Customer has no Completed jobs'
        ], 400);
    }

    return response()->json([
        'status' => 'success',
        'jobs' => $jobs
    ], 200);
    }
//////////////////////////////    Change Password for Customer /////////////////////////////////////////////////////////
    public function change_customer_password(Request $request)
    {
    // Validate the incoming request
    $validator = Validator::make($request->all(), [
        'id' => 'required|int',
        'old_password' => 'required',
        'new_password' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors(),
        ], 400);
    }

    // Get the user based on the id from huzaifa_users table
    $user = DB::table('huzaifa_users')->where('id', $request->id)->first();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not found',
        ], 404);
    }

    // Check if the old password matches the stored password (assuming password is hashed)
    if (!Hash::check($request->old_password, $user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Old password is incorrect',
        ], 400);
    }

    // Hash the new password
    $hashedNewPassword = Hash::make($request->new_password);

    // Update the password in the database
    DB::table('huzaifa_users')
        ->where('id', $request->id)
        ->update(['password' => $hashedNewPassword]);

    return response()->json([
        'status' => 'success',
        'message' => 'Password updated successfully',
    ], 200);
    }

///////////////////////////////////////////   Delete Customer Account    /////////////////////////////////////////
    public function delete_customer_account(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|int',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        // Get the user based on the customer_id from huzaifa_users table
        $user = DB::table('huzaifa_users')->where('id', $request->customer_id)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer ID does not exist',
            ], 400);
        }

        // Delete the user from the database
        DB::table('huzaifa_users')->where('id', $request->customer_id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Account deleted successfully',
        ], 200);
    }

/////////////////////////////////////     Customer wallet Balance details     ////////////////////////////////
    public function showCustomerWalletBalance(Request $request)
    {
    // Validate customer_id
    $validator = Validator::make($request->all(), [
        'customer_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    // Retrieve the customer using customer_id from huzaifa_users table
    $customer = DB::table('huzaifa_users')->where('id', $request->customer_id)->first();

    if (!$customer) {
        return response()->json([
            'status' => 'error',
            'message' => 'Customer not found'
        ], 404);
    }

    // Return wallet balance
    return response()->json([
        'status' => 'success',
        'message' => 'Wallet Balance Retrieved Successfully!',
        'wallet_balance' => $customer->wallet_balance,
    ], 200);
    }


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////          Employee Side     /////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ///////////////////////////  Employee Sign Up Api ///////////////////////////////////////////////////////////////
    public function signup_employee(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'profile' => 'required|string', // Base64 validation for profile image
            'id_image' => 'required|string', // Base64 validation for ID image
            'form_image' => 'required|string', // Base64 validation for form image
            'phone' => 'required|string',
            'user_type' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Check if email already exists
        $emailExists = DB::table('huzaifa_employees')->where('email', $request->email)->exists();

        if ($emailExists) {
            return response()->json(['message' => 'Email already exists'], 409); // 409 Conflict
        }

        // Handling profile image
        $profileFilename = $this->decodeProfileImage($request->input('profile'));
        if (!$profileFilename) {
            return response()->json(['message' => 'Invalid profile image format'], 400);
        }

        // Handling ID image
        $idImageFilename = $this->decodeIdImage($request->input('id_image'));
        if (!$idImageFilename) {
            return response()->json(['message' => 'Invalid ID image format'], 400);
        }

        // Handling form image
        $formImageFilename = $this->decodeFormImage($request->input('form_image'));
        if (!$formImageFilename) {
            return response()->json(['message' => 'Invalid form image format'], 400);
        }

        // Insert data into the database
        $employee = DB::table('huzaifa_employees')->insert([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'profile' => $profileFilename, // Store profile image filename
            'id_image' => $idImageFilename, // Store ID image filename
            'form_image' => $formImageFilename, // Store form image filename
            'user_type' => $request->user_type,
            'balance' => 0
        ]);

        // Return the response
        if ($employee) {
            return response()->json([
                'status' => 'Success',
                'message' => 'Employee Created Successfully!',
            ], 200);
        } else {
            return response()->json([
                'status' => 'Error',
                'message' => 'Employee Could not be Created',
            ], 400);
        }
    }

    // Function to decode base64 profile image and save it
    private function decodeProfileImage($base64String)
    {
        return $this->saveBase64Image($base64String, 'profile_image');
    }

    // Function to decode base64 ID image and save it
    private function decodeIdImage($base64String)
    {
        return $this->saveBase64Image($base64String, 'id_image');
    }

    // Function to decode base64 form image and save it
    private function decodeFormImage($base64String)
    {
        return $this->saveBase64Image($base64String, 'form_image');
    }

    // General function to save base64 image data in public/uploads
    private function saveBase64Image($base64String, $prefix)
    {
        $base64Data = $this->extractBase64empData($base64String);

        if (!$base64Data) {
            return false;
        }

        // Generate a unique filename
        $filename = $prefix . '_' . time() . '.png';

        // Ensure the public/uploads directory exists
        $uploadPath = public_path('uploads');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Save the image in public/uploads
        $filePath = $uploadPath . '/' . $filename;
        file_put_contents($filePath, base64_decode($base64Data));

        // Return the filename for storing in the database
        return 'uploads/' . $filename;
    }

    // Function to extract base64 image data
    private function extractBase64empData($base64String)
    {
        return preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
    }


// ///////////////////////////  Employee Log In Api ///////////////////////////////////////////
    public function login_employees(Request $request){
    // Custom Validation
    if(empty($request->email) || empty($request->password)){
    return response()->json([
    'status' => 'error',
    'message' => 'All Fields are Required' 
    ], 200);
    }

// Retrieving the User by Email
    $user = DB::table('huzaifa_employees')->where('email', $request->email)->first();

// Checking If the user exists and the password is correct
    if($user && Hash::check($request->password, $user->password)){
    return response()->json([
    'status' => 'success',
    'Message' => 'User Logged in Successfully! ',
    'data' => $user
    ], 200);
    }else{
    return response()->json([
    'status' => 'error',
    'message' => 'Email and Password does not Match!'
    ], 400);
    }
    }
// ////////////////////////////////////// Employee Forget Password Api /////////////////////////////
    public function forget_passwordforemp(Request $request){
    // Storing the given email in $email variable
    $email = $request->input('email');

// Checking If the user exists
    $user = DB::table('huzaifa_employees')->where('email', $email)->first();

    if(!$user){
    return response()->json([
        'status' => 'Error',
        'message' => 'User does not exists'
    ], 400);
    }

// Generating a 4-digit random number as OTP
    $otp = rand(1000, 9999);
    DB::table('huzaifa_employees_otp')->updateOrInsert(
    ['employee_email' => $email],
    ['otp' => $otp]
    );

// Sending OTP to User's email
    $this->sendOtpEmailforemp($email, $otp);
    return response()->json([
    'status' => 'Success',
    'message' => 'OTP has been sent to your Email',
    'OTP' => $otp,
    ]);
    }

    private function sendOtpEmailforemp($email, $otp)
    {
    $subject = "Your Password Reset OTP";
    $message = "Your OTP for resetting your password is: $otp";

    Mail::raw($message, function ($mail) use ($email, $subject) {
    $mail->to($email)
        ->subject($subject);
    });
    }    

// //////////////////////////////////////  Employee Update Password Api ///////////////////////////////////////
    public function reset_passwordforemp(Request $request){
    $email = $request->input('email');
    $newpassword = $request->input('password');

// Checking if the user exists in the database table
    $user = DB::table('huzaifa_employees')->where('email', $email)->first();

    if (!$user) {
    return response()->json([
        'status' => 'error',
        'message' => 'User does not exist'
    ], 400);
    }

// Hashing the new password
    $hashpassword = Hash::make($newpassword);

// Update the password in the database
    DB::table('huzaifa_employees')
    ->where('email', $email)
    ->update(['password' => $hashpassword]);

    return response()->json([
    'status' => 'success',
    'message' => 'Password Updated Successfully'
    ], 200);
    }
    // //////////////////////////////////////    Show Pending Jobs Api | Employee side -> My Jobs //////////////////////////
    public function show_pending_jobs(){
        $jobs = DB::table('huzaifa_create_jobs')->where('status', 'Pending')->get();
        return response()->json([
            'status' => 'success',
            'jobs' => $jobs
        ], 200);
    }
    // //////////////////////////////////////    Accepting Jobs Api //////////////////////////////
    public function accepted_jobs(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'employee_id' => 'required|integer',
            'job_id' => 'required|integer',
        ]);
    
        // Retrieve the job details from the huzaifa_create_jobs table
        $job = DB::table('huzaifa_create_jobs')->where('id', $request->job_id)->first();
    
        if (!$job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found'
            ], 400);
        }

        // Check if the job is already accepted
        if ($job->status === 'Accepted') {
            return response()->json([
                'status' => 'error',
                'message' => 'This job has already been accepted'
            ], 400);
        }

        // Update the job status to 'accepted' in the huzaifa_create_jobs table
        DB::table('huzaifa_create_jobs')
            ->where('id', $request->job_id)
            ->update(['status' => 'Accepted']);
    
        // Insert the job data into the huzaifa_accepted_jobs table
        DB::table('huzaifa_accepted_jobs')->insert([
            'job_id' => $job->id,
            'customer_id' => $job->customer_id,
            'employee_id' => $request->employee_id,
            'image' => $job->image,
            'name' => $job->name,
            'job_date' => $job->job_date,
            'start_time' => $job->start_time,
            'end_time' => $job->end_time,
            'special_instructions' => $job->special_instructions,
            'location' => $job->location,
            'amount' => $job->amount,
            'service_charges' => $job->service_charges,
            'tax' => $job->tax,
            'total_price' => $job->total_price,
            'status' => 'Accepted',
        ]);
        
        // Retrieve the updated job details to include the updated status
        $updatedJob = DB::table('huzaifa_create_jobs')->where('id', $request->job_id)->first();
    
        // Return a success message and the accepted job details
        return response()->json([
            'status' => 'success',
            'message' => 'Job accepted and inserted successfully',
            'accepted_job' => $updatedJob
        ], 200);
    }
    
// /////////////////////////  Displaying Accepted Jobs by Employee Id Api ///////////////////
    public function show_employee_jobs(Request $request)
    {
    // Validation
    $validator = Validator::make($request->all(), [
        'employee_id' => 'required|integer', 
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => $validator->errors(),
        ], 400);
    }

    // Getting Customer ID from Request
    $employeeId = $request->input('employee_id');

    // Check if the customer_id does not exist
    $existingCustomer = DB::table('huzaifa_accepted_jobs')->where('employee_id', $employeeId)->exists();

    if (!$existingCustomer) {
        // If customer_id does not exist, return an error
        return response()->json([
            'status' => 'error',
            'message' => 'Employee ID does not exist.',
        ], 400); 
    }

    // Proceed if the customer_id exists
    $acceptedJobs = DB::table('huzaifa_accepted_jobs')->where('status', 'Accepted')->get();

    return response()->json([
        'status' => 'success',
        'data' => $acceptedJobs,
    ], 200);
    }

      // //////////////////////////////////////    Started/On going Jobs Api //////////////////////////////
      public function started_jobs(Request $request)
      {
          // Validate the incoming request
          $request->validate([
              'employee_id' => 'required|integer',
              'job_id' => 'required|integer',
          ]);
      
          // Retrieve the job details from the huzaifa_create_jobs table
          $job = DB::table('huzaifa_accepted_jobs')->where('job_id', $request->job_id)->first();
      
          if (!$job) {
              return response()->json([
                  'status' => 'error',
                  'message' => 'Job not found'
              ], 400);
          }

          $emp = DB::table('huzaifa_employees')->where('id', $request->employee_id)->first();
      
          if (!$emp) {
              return response()->json([
                  'status' => 'error',
                  'message' => 'Employee not found'
              ], 400);
          }
                  
          // Check if the job is already accepted
          if ($job->status === 'On Going') {
              return response()->json([
                  'status' => 'error',
                  'message' => 'This job has already been Started'
              ], 400);
          }
      
          // Update the job status to 'accepted' in the huzaifa_create_jobs table
          DB::table('huzaifa_create_jobs')
              ->where('id', $request->job_id)
              ->update(['status' => 'On Going']);

          DB::table('huzaifa_accepted_jobs')
              ->where('job_id', $request->job_id)
              ->update(['status' => 'On Going']);
      
          // Insert the job data into the huzaifa_started_jobs table
          DB::table('huzaifa_started_jobs')->insert([
              'job_id' => $job->id,
              'customer_id' => $job->customer_id,
              'employee_id' => $request->employee_id,
              'image' => $job->image,
              'name' => $job->name,
              'job_date' => $job->job_date,
              'start_time' => $job->start_time,
              'end_time' => $job->end_time,
              'special_instructions' => $job->special_instructions,
              'location' => $job->location,
              'amount' => $job->amount,
              'service_charges' => $job->service_charges,
              'tax' => $job->tax,
              'total_price' => $job->total_price,
              'status' => 'On Going',
          ]);
      
          // Retrieve the updated job details to include the updated status
          $startedJob = DB::table('huzaifa_accepted_jobs')->where('job_id', $request->job_id)->first();
      
          // Return a success message and the accepted job details
          return response()->json([
              'status' => 'success',
              'message' => 'Job Started successfully',
              'Started_job' => $startedJob
          ], 200);
      }
    // /////////////////////////  Displaying Started/On GOingJobs by Employee Id Api ///////////////////
    public function show_started_jobs(Request $request)
    {
    // Validation
    $validator = Validator::make($request->all(), [
        'employee_id' => 'required|integer', 
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => $validator->errors(),
        ], 400);
    }

    // Getting Employee ID from Request
    $employeeId = $request->input('employee_id');

    // Check if the employee_id does not exist
    $existingEmployee = DB::table('huzaifa_started_jobs')->where('employee_id', $employeeId)->exists();

    if (!$existingEmployee) {
        // If employee_id does not exist, return an error
        return response()->json([
            'status' => 'error',
            'message' => 'Employee ID does not exist.',
        ], 404); 
    }

    // Proceed if the customer_id exists
    $startedJobs = DB::table('huzaifa_started_jobs')->where('status', 'On Going')->get();

    return response()->json([
        'status' => 'success',
        'data' => $startedJobs,
    ], 200);
}
// ///////////////////////////  Cancel Job Api ///////////////////////////////////////////
    public function cancelled_jobs(Request $request){
    $validator = Validator::make($request->all(), [
        'employee_id' => 'required',
        'job_id' => 'required'
    ]);

    if($validator->fails()){
        return response()->json([
            'error' => $validator->errors()
        ], 400);
    }

    // Getting Job Id from request

    $job = DB::table('huzaifa_accepted_jobs')->where('job_id', $request->job_id)->first();

    if(!$job){
        return response()->json([
            'status' => 'error',
            'message' => 'Job does not exists'
        ], 400);
    }
    $emp = DB::table('huzaifa_employees')->where('id', $request->employee_id)->first();

    if(!$emp){
        return response()->json([
            'status' => 'error',
            'message' => 'Employee does not exists'
        ], 400);
    }

    if($job->status == 'Cancelled'){
        return response()->json([
            'status' => 'error',
            'message' => 'The job has already been cancelled!'
        ], 400);
    }

    DB::table('huzaifa_create_jobs')
    ->where('id', $request->job_id)
    ->update(['status' => 'Cancelled']);

    DB::table('huzaifa_accepted_jobs')
    ->where('job_id', $request->job_id)
    ->update(['status' => 'Cancelled']);

    $cancelledjob = DB::table('huzaifa_accepted_jobs')->where('job_id', $request->job_id)->first();

    return response()->json([
        'status' => 'success',
        'message' => 'The job is cancelled!',
        'cancelled job' => $cancelledjob
    ], 200);

    }
// ///////////////////////////  Completed Job Api ///////////////////////////////////////////
    public function completed_jobs(Request $request){
    $validator = Validator::make($request->all(), [
        'employee_id' => 'required',
        'job_id' => 'required'
    ]);

    if($validator->fails()){
        return response()->json([
            'error' => $validator->errors()
        ], 400);
    }

    // Getting Job Id from request

    $job = DB::table('huzaifa_started_jobs')->where('job_id', $request->job_id)->first();

    if(!$job){
        return response()->json([
            'status' => 'error',
            'message' => 'Job does not exists'
        ], 400);
    }
    $emp = DB::table('huzaifa_employees')->where('id', $request->employee_id)->first();

    if(!$emp){
        return response()->json([
            'status' => 'error',
            'message' => 'Employee does not exists'
        ], 400);
    }

    if($job->status == 'Completed'){
        return response()->json([
            'status' => 'error',
            'message' => 'The job has already been Completed'
        ], 400);
    }

    DB::table('huzaifa_create_jobs')
    ->where('id', $request->job_id)
    ->update(['status' => 'Completed']);

    DB::table('huzaifa_started_jobs')
    ->where('job_id', $request->job_id)
    ->update(['status' => 'Completed']);

    $completedjob = DB::table('huzaifa_started_jobs')->where('job_id', $request->job_id)->first();

    return response()->json([
        'status' => 'success',
        'message' => 'The job is Completed!',
        'completed job' => $completedjob
    ], 200);

    }

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////// Enabling Chat Connections /////////////////////////////////////////////////////

////////////////////////////  Starting the Chat between two ids ////////////////////////////////////////////////
    public function createChat(Request $request)
    {
    // Validate the request payload for both sender_id and receiver_id
    $validatedData = $request->validate([
        'sender_id' => 'required|integer',
        'receiver_id' => 'required|integer',
    ]);

    // Check if the sender_id exists in the huzaifa_employees table
    $employeeExists = DB::table('huzaifa_employees')
        ->where('id', $validatedData['sender_id'])
        ->exists();

    // Check if the receiver_id exists in the huzaifa_users table
    $userExists = DB::table('huzaifa_users')
        ->where('id', $validatedData['receiver_id'])
        ->exists();

    // If the employee doesn't exist, return an error
    if (!$employeeExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Sender (Employee) ID not found.',
        ], 400);
    }

    // If the user doesn't exist, return an error
    if (!$userExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Receiver (User) ID not found.',
        ], 400);
    }

    // Check if either sender_id or receiver_id already exists in the huzaifa_create_chats table
    $chatExists = DB::table('huzaifa_create_chats')
        ->where('sender_id', $validatedData['sender_id'])
        ->orWhere('receiver_id', $validatedData['receiver_id'])
        ->exists();

    // If a chat already exists with either sender_id or receiver_id, return an error
    if ($chatExists) {
        return response()->json([
            'status' => 'success',
            'message' => 'Chat has already been started.',
        ], 200);
    }

    // If both IDs exist and no chat exists, insert the chat into the huzaifa_create_chats table
    DB::table('huzaifa_create_chats')->insert([
        'sender_id' => $validatedData['sender_id'],
        'receiver_id' => $validatedData['receiver_id'],
        'status' => 'Chat enabled', // Default status
        'created_at' => now(), // Current timestamp for created_at
        'updated_at' => now(), // Current timestamp for updated_at
    ]);

    // Return success response
    return response()->json([
        'status' => 'success',
        'message' => 'Chat created successfully.',
    ], 200);
    }

////////////////////////////////////  Sending Message/Image in Chat //////////////////////////////////// 
    public function send_message(Request $request)
    {
    // Validating sender_id, receiver_id, job_id, image, message, and message_type
    $request->validate([
        'sender_id' => 'required|integer',
        'receiver_id' => 'required|integer',
        'image' => 'nullable|string', // Base64 validation (nullable for text messages)
        'message' => 'nullable|string', // Nullable for attachment messages
        'message_type' => 'required|string|in:text,attachment' // Ensure it can only be 'text' or 'attachment'
    ]);

    // Getting the sender_id, receiver_id, image, message, and message_type from the request
    $sender_id = $request->input('sender_id');
    $receiver_id = $request->input('receiver_id');
    $encodedImage = $request->input('image');
    $message = $request->input('message');
    $message_type = $request->input('message_type');

    // Checking if sender exists in huzaifa_users table
    $senderexists = DB::table('huzaifa_users')->where('id', $sender_id)->exists();

    // Checking if receiver exists in huzaifa_employees table
    $receiverexists = DB::table('huzaifa_employees')->where('id', $receiver_id)->exists();

    // If sender, receiverexist
    if ($senderexists && $receiverexists) {
        // Prepare data for insertion based on message type
        $data = [
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'status' => 'Un Read', // By default message is unread
            'created_at' => now(), // Current timestamp for created_at
            'updated_at' => now(),
            'message_type' => $message_type // Insert message_type
        ];

        if ($message_type === 'text') {
            // If the message is text, include the message and set the image to null
            $data['message'] = $message;
            $data['image'] = null; // No image for text messages
        } elseif ($message_type === 'attachment') {
            // If the message is an attachment, include the image and set the message to null
            $filename = $this->saveQRCodeImage($encodedImage, 'qrcode_chat_image');

            if (!$filename) {
                return response()->json(['message' => 'Invalid image format'], 400);
            }

            $data['image'] = $filename; // Store filename instead of base64
            $data['message'] = null; // No message for attachment messages
        }

        // Insert data into huzaifa_messages table and get the inserted ID
        $insertedId = DB::table('huzaifa_messages')->insertGetId($data);

        // Retrieve the inserted entry by its ID
        $insertedChat = DB::table('huzaifa_messages')->where('id', $insertedId)->first();

        // Return success response with the inserted chat entry details
        return response()->json([
            'status' => 'success',
            'message' => 'Chat Enabled between Sender and Receiver',
            'data' => $insertedChat // Include only the inserted chat entry details
        ], 200);
    } else {
        // Return error response if sender, receiver, or job not found
        return response()->json([
            'status' => 'error',
            'message' => 'Sender, Receiver, or Job Id not found'
        ], 400);
    }
    }

// General function to save base64 QR code image data in public/uploads
    private function saveQRCodeImage($base64String, $prefix)
    {
    $base64Data = $this->extractBase64QRCodeData($base64String);

    if (!$base64Data) {
        return false;
    }

    // Generate a unique filename
    $filename = $prefix . '_' . time() . '.png';

    // Ensure the public/uploads directory exists
    $uploadPath = public_path('uploads');
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    // Save the image in public/uploads
    $filePath = $uploadPath . '/' . $filename;
    file_put_contents($filePath, base64_decode($base64Data));

    // Return the filename for storing in the database
    return 'uploads/' . $filename;
    }

// Function to extract base64 QR code image data
    private function extractBase64QRCodeData($base64String)
    {
    return preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
    }

//////////////////////////////////////  Get/Fetch Messages //////////////////////////////////////////////////////////
    public function fetch_messages(Request $request)
    {
    // Validate sender_id and receiver_id in the request payload
    $request->validate([
        'sender_id' => 'required|integer',
        'receiver_id' => 'required|integer',
    ]);

    // Get sender_id and receiver_id from request
    $sender_id = $request->input('sender_id');
    $receiver_id = $request->input('receiver_id');

    // Verify if any message exists with these sender_id and receiver_id
    $messageExists = DB::table('huzaifa_messages')
        ->where('sender_id', $sender_id)
        ->where('receiver_id', $receiver_id)
        ->exists();

    if ($messageExists) {
        // Update the status of matching messages to 'Read'
        DB::table('huzaifa_messages')
            ->where('sender_id', $sender_id)
            ->where('receiver_id', $receiver_id)
            ->update(['status' => 'Read', 'updated_at' => now()]);

        // Fetch all updated messages
        $messages = DB::table('huzaifa_messages')
            ->where('sender_id', $sender_id)
            ->where('receiver_id', $receiver_id)
            ->get();

        // Return the updated messages
        return response()->json([
            'status' => 'success',
            'message' => 'Messages fetched and marked as Read!',
            'data' => $messages // Return the updated messages
        ], 200);
    } else {
        // If no messages are found, return an error response
        return response()->json([
            'status' => 'error',
            'message' => 'No messages found between the sender and receiver'
        ], 400);
    }
    }


//////////////////////////////////////////    Job Ratings and Reveiws //////////////////////////////////////////////////
    public function job_rating(Request $request) {
    // Validate job_rating and job_review input
    $validated = $request->validate([
        'customer_id' => 'required',                  
        'employee_id' => 'required',                        
        'job_id'      => 'required',                         
        'job_rating'  => 'required',            
        'job_review'  => 'required'                            
    ]);

    // Extract validated input
    $customer_id = $request->input('customer_id');
    $employee_id = $request->input('employee_id');
    $job_id = $request->input('job_id');
    $job_rating = $request->input('job_rating');
    $job_review = $request->input('job_review');

    // Verify if customer_id exists in huzaifa_users table
    $customerExists = DB::table('huzaifa_users')->where('id', $customer_id)->exists();
    if (!$customerExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid customer ID'
        ], 400);
    }

    // Verify if employee_id exists in huzaifa_employees table
    $employeeExists = DB::table('huzaifa_employees')->where('id', $employee_id)->exists();
    if (!$employeeExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid employee ID'
        ], 400);
    }

    // Verify if job_id exists in huzaifa_create_jobs table
    $jobExists = DB::table('huzaifa_create_jobs')->where('id', $job_id)->exists();
    if (!$jobExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid job ID'
        ], 400);
    }

    // Check if the job_id already exists in the huzaifa_job_ratings table
    $existingRating = DB::table('huzaifa_job_ratings')->where('job_id', $job_id)->first();
    if ($existingRating) {
        return response()->json([
            'status' => 'error',
            'message' => 'Job rating for this job ID already exists.'
        ], 400);
    }

    // Insert the new rating and review into huzaifa_job_ratings table and get the ID of the inserted record
    $ratingId = DB::table('huzaifa_job_ratings')->insertGetId([
        'customer_id' => $customer_id,
        'employee_id' => $employee_id,
        'job_id'      => $job_id,
        'job_rating'  => $job_rating,
        'job_review'  => $job_review,

    ]);

    // Fetch the inserted job rating by its ID
    $insertedRating = DB::table('huzaifa_job_ratings')->where('id', $ratingId)->first();

    // Return the success response with the inserted rating details
    return response()->json([
        'status' => 'success',
        'message' => 'Job rating and review submitted successfully.',
        'data' => $insertedRating
    ], 200);
    }

//////////////////////////////////////// Show Job Rating details by job_id ////////////////////////////////////
    public function show_job_rating(Request $request)
    {
    // Validation
    $validator = Validator::make($request->all(), [
        'job_id' => 'required|integer', 
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => $validator->errors(),
        ], 400);
    }

    // Getting Job ID from Request
    $jobId = $request->input('job_id');
    
    // Check if the Job_id does not exist
    $existingjob = DB::table('huzaifa_job_ratings')->where('id', $jobId)->exists();

    if (!$existingjob) {
        // If job_id does not exist, return an error
        return response()->json([
            'status' => 'error',
            'message' => 'Job ID does not exist.',
        ], 400); 
    }

    // Proceed if the job_id exists
    $createdJobs = DB::table('huzaifa_job_ratings')->where('id', $jobId)->get();

    return response()->json([
        'status' => 'success',
        'data' => $createdJobs,
    ], 200);

    }
 //////////////////////////////////////// Transaction Api //////////////////////////////////////////////////////
    public function transferAmount(Request $request) {
    // Validate the input
    $validated = $request->validate([
        'employee_id' => 'required',
        'job_id' => 'required',
        'customer_id' => 'required',
    ]);

    $employee_id = $request->input('employee_id');
    $customer_id = $request->input('customer_id');
    $job_id = $request->input('job_id');

    // Verifying the employee_id from the huzaifa_employees table
    $employeeExists = DB::table('huzaifa_employees')->where('id', $employee_id)->exists();
    if (!$employeeExists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid Employee ID'
        ], 400);
    }

    // Verifying the job_id and the status as Completed from the huzaifa_create_jobs table
    $job = DB::table('huzaifa_create_jobs')->where('id', $job_id)->first();
    if (!$job || $job->status !== 'Completed') {
        return response()->json([
            'status' => 'error',
            'message' => $job ? 'Job status is not Completed' : 'Invalid Job ID'
        ], 400);
    }
      
    // Check if the job has already been paid
    if ($job->payment_status === 'Paid') {
        return response()->json([
            'status' => 'error',
            'message' => 'This job has already been paid.'
        ], 400);
    }

    // Verifying customer_id from the huzaifa_users table
    $customer = DB::table('huzaifa_users')->where('id', $customer_id)->first();
    if (!$customer) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid Customer ID'
        ], 400);
    }

    // Retrieving the amount from huzaifa_create_jobs
    $amount = $job->amount;
    if ($customer->wallet_balance < $amount) {
        return response()->json([
            'status' => 'error',
            'message' => 'Insufficient funds in customer wallet'
        ], 400);
    }

    // Performing the amount deduction from customer and adding to the employee
    DB::transaction(function () use ($amount, $customer_id, $employee_id, $job_id) {
        // Deduct the amount from customer wallet_balance
        DB::table('huzaifa_users')
            ->where('id', $customer_id)
            ->decrement('wallet_balance', $amount);

        // Add the amount to employee balance
        DB::table('huzaifa_employees')
            ->where('id', $employee_id)
            ->increment('balance', $amount);

        // Marking the job as paid by updating the payment_status column
        DB::table('huzaifa_create_jobs')
            ->where('id', $job_id)
            ->update(['payment_status' => 'Paid']);
    });

    return response()->json([
        'status' => 'success',
        'message' => 'Amount successfully transferred from customer to employee!'
    ], 200);
    }

//////////////////////////////    Change Password for Employee /////////////////////////////////////////////////////////
    public function change_employee_password(Request $request)
    {
    // Validate the incoming request
    $validator = Validator::make($request->all(), [
        'id' => 'required|int',
        'old_password' => 'required',
        'new_password' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors(),
        ], 400);
    }

    // Get the user based on the id from huzaifa_employees table
    $employee = DB::table('huzaifa_employees')->where('id', $request->id)->first();

    if (!$employee) {
        return response()->json([
            'status' => 'error',
            'message' => 'Employee not found',
        ], 404);
    }

    // Check if the old password matches the stored password (assuming password is hashed)
    if (!Hash::check($request->old_password, $employee->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Old password is incorrect',
        ], 400);
    }

    // Hash the new password
    $hashedNewPassword = Hash::make($request->new_password);

    // Update the password in the database
    DB::table('huzaifa_users')
        ->where('id', $request->id)
        ->update(['password' => $hashedNewPassword]);

    return response()->json([
        'status' => 'success',
        'message' => 'Password updated successfully',
    ], 200);
}

///////////////////////////////////////////   Delete Employee Account    /////////////////////////////////////////
public function delete_employee_account(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|int',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        // Get the user based on the customer_id from huzaifa_users table
        $employee = DB::table('huzaifa_employees')->where('id', $request->employee_id)->first();

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee ID does not exist',
            ], 400);
        }

        // Delete the user from the database
        DB::table('huzaifa_employees')->where('id', $request->employee_id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Account deleted successfully',
        ], 200);
    }
/////////////////////////////////////  Calculate Amount for extra time  //////////////////////////////////////////////////
public function calculate_extratime_payment(Request $request)
{
    // Validation
    $validator = Validator::make($request->all(), [
        'job_id' => 'required|integer'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 401);
    }

    // Retrieve job details from huzaifa_create_jobs table
    $job = DB::table('huzaifa_create_jobs')->where('id', $request->job_id)->first();

    if (!$job) {
        return response()->json(['status' => 'error', 'message' => 'Invalid job ID'], 400);
    }

    // Extract values from the job record
    $previousAmount = $job->amount;
    $startTime = strtotime($job->start_time);
    $endTime = strtotime($job->end_time);
    $currentTime = time(); // Get current device time
    $extraMinutes = 0;
    $extraAmount = 0;
    $minuteRate = 0.35;

    // Checking if extra time is needed
    if ($currentTime > $endTime) {
        $extraMinutes = ($currentTime - $endTime) / 60;
        $extraAmount = $extraMinutes * $minuteRate; // Calculate extra amount
        $bookedClosed = date('Y-m-d H:i', $currentTime); // Use current time for booked_closed if extra time exists
    } else {
        $bookedClosed = date('Y-m-d H:i', $endTime); // Use end_time if no extra time exists
    }

    // Calculate total amount
    $totalAmount = $previousAmount + $extraAmount;

    // Retrieve service charges and tax from the job
    $serviceCharges = $job->service_charges;
    $tax = $job->tax;

    // Update the huzaifa_create_jobs table with the calculated values
    DB::table('huzaifa_create_jobs')->where('id', $job->id)->update([
        'amount' => $previousAmount,  // Updating previous amount in amount field
        'service_charges' => $serviceCharges,  // Update service charges
        'tax' => $tax,  // Update tax
        'total_price' => $totalAmount  // Update total amount
    ]);

    // Return response with details
    return response()->json([
        'status' => 'Success',
        'message' => 'Payment Calculated Successfully!',
        'data' => [
            'total_amount' => $totalAmount,   // Total amount including extra charges
            'previous_amount' => $previousAmount,
            'extra_amount' => $extraAmount,
            'service_charges' => $serviceCharges,
            'tax' => $tax,
            'booked_time' => date('Y-m-d H:i', $startTime) . ' - ' . date('Y-m-d H:i', $endTime),
            'booked_closed' => $bookedClosed, // Updated booked_closed logic
            'extra_time' => $extraMinutes > 0 ? round($extraMinutes, 2) . ' minutes' : 'No extra time',
        ]
    ], 200);
}
/////////////////////////////////////     Employee wallet Balance details     ////////////////////////////////
public function showemployeeWalletBalance(Request $request)
{
    // Validate customer_id
    $validator = Validator::make($request->all(), [
        'employee_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    // Retrieve the Employee using customer_id from huzaifa_users table
    $employee = DB::table('huzaifa_employees')->where('id', $request->employee_id)->first();

    if (!$employee) {
        return response()->json([
            'status' => 'error',
            'message' => 'Employee not found'
        ], 400);
    }
        
    // Return wallet balance
    return response()->json([
        'status' => 'success',
        'message' => 'Wallet Balance Retrieved Successfully!',
        'wallet_balance' => $employee->balance,
    ], 200);
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////    Dashboard Controllers   ////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function login(Request $request)
    {
        // Validate the request data
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Retrieve the admin from the database
        $admin = DB::table('huzaifa_admins')
            ->where('email', $request->input('email'))
            ->first();

        // Check if the admin exists and the password matches
        if ($admin && $admin->password === $request->input('password')) {
            // Store the admin's information in the session
            Session::put('admin_id', $admin->id);
            Session::put('admin_email', $admin->email);
            return redirect('/dashboardd'); // Redirect to the dashboard
        }

        // If login fails, return back with an error message
        return redirect()->back()->with('error', 'Invalid email or password.')->withInput();
    }
    
    //////////////////////////////////  Logout ///////////////////////////////////////////////
    public function logout()
    {
        // Destroy the session
        Session::flush();

        // Redirect to the login page
        return redirect('/loginpage')->with('success', 'Logged out successfully.');
    }

    //////////////////////////////   Admin Dynamic Image from the Table //////////////////////////
    public function getAdminImage()
    {
        // Fetch admin image from the database
        $admin = huzaifa_admins::first(); // Fetch the first admin, adjust this as needed
        
        // Return the view with the image path
        return view('Huzaifa_dashboard.app', compact('admin'));
    }

    /////////////////////////////////  Dashboard Index ////////////////////////////////
    public function index()
    {
        // Fetch total customers
        $totalCustomers = DB::table('huzaifa_users')->count();

        // Fetch total employees
        $totalEmployees = DB::table('huzaifa_employees')->count();

        // Fetch total jobs
        $totalJobs = DB::table('huzaifa_create_jobs')->count();

        // Fetch total started jobs (status 'On Going')
        $startedJobs = DB::table('huzaifa_create_jobs')
            ->where('status', 'On Going')
            ->count();

        // Fetch total completed jobs (status 'Completed')
        $completedJobs = DB::table('huzaifa_create_jobs')
            ->where('status', 'Completed')
            ->count();

        // Fetch total cancelled jobs (status 'Cancelled')
        $cancelledJobs = DB::table('huzaifa_create_jobs')
            ->where('status', 'Cancelled')
            ->count();

        // Pass the data to the view
        return view('Huzaifa_dashboard.dashboard', [
            'totalCustomers' => $totalCustomers, 
            'totalEmployees' => $totalEmployees,
            'totalJobs'=> $totalJobs,
            'startedJobs'=> $startedJobs,
            'completedJobs' => $completedJobs,
            'cancelledJobs'=> $cancelledJobs     
           ]);
    }
    //////////////////////////////////  Customers Dynamic data //////////////////////////
    public function showCustomers()
    {
        // Fetch customers details from huzaifa_users table
        $customers = DB::table('huzaifa_users')
        ->select('id', 'first_name', 'last_name', 'email', 'wallet_balance')
        ->get();
        
        // Pass the customers data to the view
        return view('Huzaifa_dashboard.customers', compact('customers'));
    }
    
    //////////////////////////////////  Employees Dynamic data //////////////////////////
    public function showEmployees()
    {
    // Fetch employee details from huzaifa_employees table
    $employees = DB::table('huzaifa_employees')
        ->select('id', 'profile', 'full_name', 'phone', 'email')
        ->get();

    // Pass the employee data to the view
    return view('Huzaifa_dashboard.employees', compact('employees'));
    }

    ///////////////////////////////// started jobs /////////////////////////////////////
    public function startedjobs()
    {
        // Fetch started jobs where status is 'On Going'
        $startedJobs = DB::table('huzaifa_create_jobs')
            ->where('status', 'On Going')
            ->get();

        // Pass the data to the view
        return view('Huzaifa_dashboard.started_jobs', compact('startedJobs'));
    }

    ///////////////////////////////// Completed jobs /////////////////////////////////////
    public function completedjobs()
    {
        // Fetch started jobs where status is 'On Going'
        $completedJobs = DB::table('huzaifa_create_jobs')
            ->where('status', 'Completed')
            ->get();

        // Pass the data to the view
        return view('Huzaifa_dashboard.completed_jobs', compact('completedJobs'));
    }

    ///////////////////////////////// Cancelled jobs /////////////////////////////////////
    public function cancelledjobs()
    {
        // Fetch started jobs where status is 'On Going'
        $cancelledJobs = DB::table('huzaifa_create_jobs')
            ->where('status', 'Cancelled')
            ->get();

        // Pass the data to the view
        return view('Huzaifa_dashboard.cancelled_job', compact('cancelledJobs'));
    }

    /////////////////////////// Delete customer Entry /////////////////////////////////////
    public function delete_customer($id)
    {
        // Check if the customer exists in huzaifa_users
        $customer = DB::table('huzaifa_users')->where('id', $id)->first();

        if (!$customer) {
            // Redirect back with error message if the customer is not found
            return redirect()->back()->with('error', 'Customer not found!');
        }

        // Delete the customer from the huzaifa_users table
        DB::table('huzaifa_users')->where('id', $id)->delete();

        // Redirect back with success message
        return redirect()->back()->with('success', 'Customer deleted successfully!');
    }

    /////////////////////// Update Customer Entry ////////////////////////////////////////
    public function updateUser(Request $request, $id)
    {
        // Validate request
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);
    
        // Check if the email already exists in the table
        $existingUser = DB::table('huzaifa_users')->where('email', $request->email)->first();
        if ($existingUser && $existingUser->id != $id) {
            return redirect()->back()->with('error', 'Email already exists.');
        }
    
        // Update the user
        DB::table('huzaifa_users')->where('id', $id)->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
        ]);
    
        // Flash the success message
        return redirect()->back()->with('success', 'User updated successfully.');
    }

    ///////////////////////////////////// Employee Delete Button /////////////////////////////////
    public function delete_employee($id)
    {
    // Check if the employee exists in huzaifa_employees
    $employee = DB::table('huzaifa_employees')->where('id', $id)->first();

    if (!$employee) {
        // Redirect back with error message if the employee is not found
        return redirect()->back()->with('error', 'Employee not found!');
    }

    // Delete the employee from the huzaifa_employees table
    DB::table('huzaifa_employees')->where('id', $id)->delete();
    
    // Redirect back with success message
    return redirect()->back()->with('success', 'Employee deleted successfully!');
    }

    ///////////////////////////////////// Employee Update Button /////////////////////////////////
    public function updateEmployee(Request $request, $id)
    {
    // Validate request
    $request->validate([
        'full_name' => 'required|string|max:255',
        'phone' => 'required|string|max:15',
        'email' => 'required|email|max:255',
    ]);

    // Check if the email already exists in the table
    $existingEmployee = DB::table('huzaifa_employees')->where('email', $request->email)->first();
    if ($existingEmployee && $existingEmployee->id != $id) {
        return redirect()->back()->with('error', 'Email already exists.');
    }

    // Update the employee
    DB::table('huzaifa_employees')->where('id', $id)->update([
        'full_name' => $request->full_name,
        'phone' => $request->phone,
        'email' => $request->email,
    ]);

    // Flash the success message
    return redirect()->back()->with('success', 'Employee updated successfully.');
    }

    //////////////////////////////// Delete Job Button ////////////////////////////////
    public function deleteJob($id)
    {
    // Delete the job
    DB::table('huzaifa_create_jobs')->where('id', $id)->delete();

    // Flash the success message
    return redirect()->back()->with('success', 'Job deleted successfully.');
    }

    ////////////////////////////////// Update Job Button /////////////////////////////////
    public function updateJob(Request $request, $id)
{
    // Validate request
    $request->validate([
        'name' => 'required|string|max:255',
        'job_date' => 'required|date',
        'start_time' => 'required',
        'end_time' => 'required',
        'special_instructions' => 'nullable|string',
        'location' => 'required|string',
    ]);

    // Update the job with the specified fields
    DB::table('huzaifa_create_jobs')->where('id', $id)->update([
        'name' => $request->name,
        'job_date' => $request->job_date,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
        'special_instructions' => $request->special_instructions, // updated field name
        'location' => $request->location,
    ]);

    // Flash the success message
    return redirect()->back()->with('success', 'Job updated successfully.');
    }

    //////////////////////////////////  Fetching Dynamic data of Admin  ///////////////////////////////////
    public function showAdminDetails()
    {
        // Fetch the first admin details from huzaifa_admins table
        $admin = DB::table('huzaifa_admins')->first();

        // Pass the admin details to the view
        return view('Huzaifa_dashboard.general_settings', compact('admin'));
    }

    ////////////////////////////////// Edit Admin Details /////////////////////////////////////////////
    public function showAdminDetails2()
    {
    // Fetch the first admin details from huzaifa_admins table
    $admin = DB::table('huzaifa_admins')->first();

    // Pass the admin details to the view
    return view('Huzaifa_dashboard.general_settings', compact('admin'));
    }

    public function updateAdminDetails(Request $request)
    {
    // Validation
    $validator = Validator::make($request->all(), [
        'name' => 'required|string',
        'email' => 'required|email',
        'old_password' => 'required|string',
        'password' => 'nullable|string|confirmed',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Fetch the admin's current details
    $admin = DB::table('huzaifa_admins')->first();

    // Check if the old password matches
    if ($request->old_password !== $admin->password) {
        return redirect()->back()->withErrors(['old_password' => 'Old password is incorrect.']);
    }

    // Prepare the data for updating
    $data = [
        'name' => $request->name,
        'email' => $request->email,
    ];

    // Check if a new password was provided
    if ($request->filled('password')) {
        $data['password'] = $request->password; // Store plain text password
    }

    // Update the admin details in the database
    DB::table('huzaifa_admins')->where('id', $admin->id)->update($data);

    return redirect()->back()->with('success', 'Admin details updated successfully!');
    }

    public function updateAdminImage(Request $request)
    {
    // Validate the image
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif'
    ]);

    // Fetch the admin details
    $admin = DB::table('huzaifa_admins')->first();

    // Handle the image upload
    if ($request->hasFile('image')) {
        // Store the image in the public/images folder
        $imageName = time() . '.' . $request->file('image')->getClientOriginalExtension();
        $request->file('image')->move(public_path('images'), $imageName);
        
        // Update the image path in the database
        DB::table('huzaifa_admins')->where('id', $admin->id)->update(['image' => 'images/' . $imageName]);
    }

    return redirect()->back()->with('success', 'Profile image updated successfully!');
    }
   
}
