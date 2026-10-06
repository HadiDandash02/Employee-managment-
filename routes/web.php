<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/employees');

Route::get('employees/data', [EmployeeController::class, 'data'])->name('employees.data');
Route::delete('employees',[EmployeeController::class, 'bulkDestroy'])->name('employees.bulk-destroy');
Route::resource('employees', EmployeeController::class);
