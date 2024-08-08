<?php

use App\Http\Controllers\Credentials;
use App\Http\Controllers\TechnicianController;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post("login", [Credentials::class, "process_login"]);
Route::post("technician/{token}", [TechnicianController::class, "getTechnicianData"]);
