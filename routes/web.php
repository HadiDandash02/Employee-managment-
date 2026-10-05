<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/employees');

Route::resource('employees', EmployeeController::class);
