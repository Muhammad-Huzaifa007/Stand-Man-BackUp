Route::get('/loginpage', function () {
    return view('Huzaifa_dashboard.login');
});

Route::get('/masterr', function () {
    return view('Huzaifa_dashboard.app');
});

Route::get('/dashboardd', function () {
    return view('Huzaifa_dashboard.dashboard');
});


Route::get('/cancelledjobs', function () {
    return view('Huzaifa_dashboard.cancelled_job');
});

Route::get('/generalsettings', function () {
    return view('Huzaifa_dashboard.general_settings');
});

/////////// Login Authentication 
Route::POST('/login', [Api2Controller::class, 'login'])->name('admin.login');

/////////// Dashboard dynamic data
Route::GET('/dashboardd', [Api2Controller::class, 'index']);

////////// Logout
Route::POST('/logout', [Api2Controller::class, 'logout'])->name('logout');

////////// customers dynamic data
Route::get('/customers', [Api2Controller::class, 'showCustomers']);

////////// Employees dynamic data
Route::get('/employees', [Api2Controller::class, 'showEmployees']);

////////// Started Jobs
Route::get('/startedjobs', [Api2Controller::class, 'startedjobs']);

////////// Completed Jobs
Route::get('/completedjobs', [Api2Controller::class, 'completedjobs']);

////////// Cancelled Jobs
Route::get('/cancelledjobs', [Api2Controller::class, 'cancelledjobs']);

//////////////////////////////////////////////////////////////////////////////

/////// Customer delete button
Route::delete('/customers/{id}', [Api2Controller::class, 'delete_customer'])->name('customers.destroy');

/////// Customer Update button
Route::put('/update-user/{id}', [Api2Controller::class, 'updateUser']);

