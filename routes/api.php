<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\Credentials;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TechnicianController;
use App\Models\Collection;
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
Route::post("token", [Credentials::class, "checkToken"]);
Route::post("technician/{token}", [TechnicianController::class, "getTechnicianData"]);
Route::post("technician/dashboard/{period}", [CollectionController::class, "getCollection"])->middleware("authenticate");

// register a new member
Route::post("register_member", [MemberController::class, "registerMember"]);
Route::post("members", [MemberController::class, "getMembers"])->middleware("authenticate");
