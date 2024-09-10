<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class Api2Controller extends Controller
{
    // //////////////////////////////////////    SIgn Up Api ///////////////////////////////////
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
        $filename = $this->decodeImageFromUrl($encodedImage);
        
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
        ]);
        
        // Return the response
        if ($user) {
            return response()->json([
                'status' => 'Succeess',
                'message' => 'User Created Successfully!',
            ], 200);
        } else {
            return response()->json([
            'message' => 'User could not be created',
             'status' => 'Error'
            ], 400);
        }
    }
    
    // Function to decode base64 image URL and save the image
    private function decodeImageFromUrl($base64String)
    {
        // Extract base64 data
        $base64Data = $this->extractBase64Data($base64String);
        
        if (!$base64Data) {
            return false;
        }
        
        // Generate a unique filename
        $filename = 'user_image_' . time() . '.png';
        
        // Save the image
        Storage::disk('public')->put($filename, base64_decode($base64Data));
        
        return $filename;
    }
    
    // Function to extract base64 image data
    private function extractBase64Data($base64String)
    {
        // Check if the string is in base64 format and remove the metadata part
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

        // Checking if the employee exists in the huzaifa_employees table
        $employee = DB::table('huzaifa_users')->where('email', $email)->first();

        if (!$employee) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Customer does not exist'
            ], 404);
        }

        // Generating a 4-digit random number as OTP
        $otp = rand(1000, 9999);

        // Storing or updating the OTP in huzaifa_employees_otp table
        DB::table('huzaifa_users_otp')->updateOrInsert(
            ['user_email' => $email],
            ['otp' => $otp]
        );
        
        // Sending OTP to employee's email
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

// //////////////////////////////////////  Update Password for Customer Api ///////////////////////////////////////
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
 // //////////////////////////////////////    Create Jobs by Customer Api /////////////////////////////////////////
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
         'amount' => $request->amount,          // Taken from payload
         'service_charges' => $request->service_charges, // Taken from payload
         'tax' => $request->tax,                // Taken from payload
         'total_price' => $request->total_price,// Taken from payload
         'status' => 'pending',  // Default status
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
 
 // Function to decode base64 image for job creation and save it
    private function decodeBase64ImageForJob($base64String)
    {
     // Extract base64 data
     $base64Data = $this->getBase64ImageContent($base64String);
     
     if (!$base64Data) {
         return false;
     }
     
     // Generate a unique filename
     $filename = 'job_image_' . time() . '.png';
     
     // Save the image
     Storage::disk('public')->put($filename, base64_decode($base64Data));
     
     return $filename;
    }
 
    // Renamed function to get base64 image content
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
        ], 404); 
    }

    // Proceed if the customer_id exists
    $createdJobs = DB::table('huzaifa_create_jobs')->where('customer_id', $customerId)->get();

    return response()->json([
        'status' => 'success',
        'data' => $createdJobs,
    ], 200);
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////       Employee Side     ////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ///////////////////////////  Employee Sign Up Api ///////////////////////////////////////////
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
    'user_type' => $request->user_type 
]);

// Return the response
if ($employee) {
    return response()->json([
        'status' => 'Succeess',
        'message' => 'Employee Created Successfully!', 
    ], 200);
} else {
    return response()->json([
        'status' => 'Error',
        'message' => 'Employee Could not be Created ', 
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

// General function to save base64 image data
private function saveBase64Image($base64String, $prefix)
{
$base64Data = $this->extractBase64empData($base64String);

if (!$base64Data) {
    return false;
}

// Generate a unique filename
$filename = $prefix . '_' . time() . '.png';

// Save the image
Storage::disk('public')->put($filename, base64_decode($base64Data));

return $filename;
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
    // //////////////////////////////////////    Show Pending Jobs Api | Employee side -> My Jobs /////////////////////////////////
    public function show_pending_jobs(){
        $jobs = DB::table('huzaifa_create_jobs')->where('status', 'pending')->get();
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
        if ($job->status === 'accepted') {
            return response()->json([
                'status' => 'error',
                'message' => 'This job has already been accepted'
            ], 400);
        }
    
        // Update the job status to 'accepted' in the huzaifa_create_jobs table
        DB::table('huzaifa_create_jobs')
            ->where('id', $request->job_id)
            ->update(['status' => 'accepted']);
    
        // Insert the job data into the huzaifa_accepted_jobs table
        DB::table('huzaifa_accepted_jobs')->insert([
            'job_id' => $job->id,
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
            'status' => 'accepted',
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
        ], 404); 
    }

    // Proceed if the customer_id exists
    $acceptedJobs = DB::table('huzaifa_accepted_jobs')->where('status', 'accepted')->get();

    return response()->json([
        'status' => 'success',
        'data' => $acceptedJobs,
    ], 200);
}

    // //////////////////////////////////////  Edit Accepted Jobs Api //////////////////////////////
    public function update_accepted_jobs(Request $request){
        $request->validate([
            'job_id' => 'required|integer',
            'image' => 'required|string',
            'name' => 'required|string',
            'job_date' => 'required|string',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'special_instructions' => 'required|string',
            'location' => 'required|string',
        ]);
        // Finding the Jobs in huzaifa_accepted_jobs table by job_id
        $job = DB::table('huzaifa_accepted_jobs')->where('job_id', $request->job_id)->first();
        
        if(!$job){
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found, Job Id is Incorrect',
            ], 400);
        }

        // Update the fields in huzaifa_acepted_jobs table
        DB::table('huzaifa_accepted_jobs')
        ->where('job_id', $request->job_id)
        ->update([
            'image' => $request->input('image'),
            'name' => $request->input('name'),
            'job_date' => $request->input('job_date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'special_instructions' => $request->input('special_instructions'),
            'location' => $request->input('location'),
        ]);

        // Fetched the Updated Job details
        $updatedjobs = DB::table('huzaifa_accepted_jobs')->where('job_id', $request->job_id)->first();

        return response()->json([
            'status' => 'Success',
            'message' => 'Job updated Successfully!',
            'Updated Job' => $updatedjobs
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

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////// Enabling Chat Connections /////////////////////////////////////////////////////
    public function enablechat(Request $request)
{
    // Validating sender_id and receiver_id
    $request->validate([
        'sender_id' => 'required|integer',
        'receiver_id' => 'required|integer',
    ]);

    // Getting the sender_id and receiver_id from the request
    $sender_id = $request->input('sender_id');
    $receiver_id = $request->input('receiver_id');

    // Checking if sender exists in huzaifa_users table
    $senderexists = DB::table('huzaifa_users')->where('id', $sender_id)->exists();

    // Checking if receiver exists in huzaifa_employees table
    $receiverexists = DB::table('huzaifa_employees')->where('id', $receiver_id)->exists();

    // If both sender and receiver exist
    if ($senderexists && $receiverexists) {
        // Insert sender_id and receiver_id into huzaifa_chat_connections table
        DB::table('huzaifa_chat_connections')->insert([
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
        ]);

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Chat Enabled between Sender and Receiver',
        ], 200);
    } else {
        // Return error response if sender or receiver not found
        return response()->json([
            'status' => 'error',
            'message' => 'Sender or Receiver Id not found'
        ], 400);
    }
}


}
