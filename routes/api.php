<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

/*
| API routes: every URL here starts with /api (added in bootstrap/app.php) and answers JSON.
| Authentication is a Sanctum token: POST /api/login, then "Authorization: Bearer <token>".
| Names start with "api." so they never clash with the web routes (employees.index, …).
*/

// 5 tries per minute per IP, so passwords cannot be guessed quickly.
Route::post('login', [Api\AuthController::class, 'login'])->middleware('throttle:5,1')->name('api.login');

Route::middleware(['auth:sanctum', 'active'])->name('api.')->group(function () {
    Route::post('logout', [Api\AuthController::class, 'logout'])->name('logout');

    // index, store, show, update, destroy (no create/edit: those are HTML forms).
    Route::apiResource('employees', Api\EmployeeController::class);
});
