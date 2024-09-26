<?php

use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\Credentials;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TechnicianController;
use App\Models\Administrator;
use App\Models\Collection;
use App\Models\Payment;
use App\Models\SuperAdministrator;
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
Route::post("technician/collection_history/{period}", [CollectionController::class, "getCollectionHistory"])->middleware("authenticate");
Route::post("technician/collection/{collection_id}", [CollectionController::class, "collectionDetail"])->middleware("authenticate");
Route::post("collection/update", [CollectionController::class, "updateMilkCollection"])->middleware("authenticate");
Route::post("collection/", [CollectionController::class, "collectionHistory"])->middleware("authenticate");

// register a new member
Route::post("register_member", [MemberController::class, "registerMember"]);
Route::post("members", [MemberController::class, "getMembers"])->middleware("authenticate");
Route::post("members/{member_id}", [MemberController::class, "getMemberData"])->middleware("authenticate");
Route::post("members/{member_id}/uploadMilk", [MemberController::class, "uploadMilk"])->middleware("authenticate");


Route::post("technician/details/{technician_id}", [TechnicianController::class, "getDetails"])->middleware("authenticate");
Route::post("technician/update/details", [TechnicianController::class, "updateUserDetails"])->middleware("authenticate");
Route::post("technician/update/credentials", [TechnicianController::class, "updateCredentials"])->middleware("authenticate");


Route::post("member/dashboard/{period}", [MemberController::class, "getMemberDashboard"])->middleware("authenticate");
Route::post("member/history", [MemberController::class, "getMemberHistory"])->middleware("authenticate");
Route::post("member/milk_details/{milk_id}", [MemberController::class, "getMilkDetails"])->middleware("authenticate");
Route::post("member/milk_status/{milk_id}", [MemberController::class, "changeMilkStatus"])->middleware("authenticate");
Route::post("member/profile", [MemberController::class, "viewProfile"])->middleware("authenticate");
Route::post("member/updateprofile", [MemberController::class, "updateMember"])->middleware("authenticate");

Route::post("admin/dashboard/{period}", [AdministratorController::class, "admin_dashboard"])->middleware("authenticate");
Route::post("admin/members", [AdministratorController::class, "admin_members"])->middleware("authenticate");
Route::post("admin/members/{member_id}", [AdministratorController::class, "member_details"])->middleware("authenticate");
Route::post("admin/member/update", [AdministratorController::class, "updateMember"])->middleware("authenticate");
Route::post("admin/member/history/{member_id}", [AdministratorController::class, "getMemberHistory"])->middleware("authenticate");
Route::post("admin/member/new", [AdministratorController::class, "addNewMember"])->middleware("authenticate");
Route::post("admin/member/info", [AdministratorController::class, "viewProfile"])->middleware("authenticate");
Route::post("admin/update_profile", [AdministratorController::class, "updateProfile"])->middleware("authenticate");
Route::post("admin/member/milk_prices", [AdministratorController::class, "getMilkPrices"])->middleware("authenticate");
Route::post("admin/milk_price/insert", [AdministratorController::class, "insertDate"])->middleware("authenticate");
Route::post("admin/milk_price/details/{price_id}", [AdministratorController::class, "getMilkDetails"])->middleware("authenticate");
Route::post("admin/milk_price/update", [AdministratorController::class, "updateMilk"])->middleware("authenticate");
Route::post("admin/milk_price/get", [AdministratorController::class, "milkPrice"])->middleware("authenticate");
Route::post("admin/milk_collection/delete/{milk_id}", [CollectionController::class, "deleteCollection"])->middleware("authenticate");
Route::post("admin/member/delete/{member_id}", [MemberController::class, "deleteMember"])->middleware("authenticate");
Route::post("admin/member/membership/{member_id}", [MemberController::class, "memberMembership"])->middleware("authenticate");
Route::post("admin/member/accept-earning", [MemberController::class, "acceptMemberPayment"])->middleware("authenticate");
Route::post("admin/member/deletePayment/{payment_id}", [MemberController::class, "declinePayment"])->middleware("authenticate");
Route::post("admin/member/pay_subscription", [MemberController::class, "paySubscription"])->middleware("authenticate");
Route::post("admin/payments/details/{payment_id}", [PaymentController::class, "payment_details"])->middleware("authenticate");
Route::post("admin/technicians", [TechnicianController::class, "getTechnicians"])->middleware("authenticate");
Route::post("admin/technician/details/{technician_id}", [TechnicianController::class, "technicianDetails"])->middleware("authenticate");
Route::post("admin/technician/update", [TechnicianController::class, "updateTechnician"])->middleware("authenticate");
Route::post("admin/technician/delete/{technician_id}", [TechnicianController::class, "deleteTechnician"])->middleware("authenticate");
Route::post("admin/technician/new", [TechnicianController::class, "registerTechnician"])->middleware("authenticate");
Route::post("admin/administrator", [AdministratorController::class, "displayAdministrators"])->middleware("authenticate");
Route::post("admin/administrator/view/{admin_id}", [AdministratorController::class, "adminDetails"])->middleware("authenticate");
Route::post("admin/administrator/delete/{admin_id}", [AdministratorController::class, "deleteAdmin"])->middleware("authenticate");
Route::post("admin/administrator/update", [AdministratorController::class, "updateAdministrator"])->middleware("authenticate");
Route::post("admin/administrator/new", [AdministratorController::class, "registerAdministrator"])->middleware("authenticate");
Route::post("admin/super_administrator", [SuperAdminController::class, "getSuperAdministrators"])->middleware("authenticate");
Route::post("admin/super_administrator/view/{super_admin_id}", [SuperAdminController::class, "superAdministratorDetails"])->middleware("authenticate");
Route::post("admin/super_administrator/delete/{super_admin_id}", [SuperAdminController::class, "deleteSuperAdmin"])->middleware("authenticate");
Route::post("admin/super_administrator/update", [SuperAdminController::class, "updateSuperAdmin"])->middleware("authenticate");
Route::post("admin/super_administrator/new", [SuperAdminController::class, "newSuperAdmin"])->middleware("authenticate");
Route::get("admin/payment/receipt/{payment_id}", [PaymentController::class, "paymentReceipt"]);

// generate report
Route::get("admin/reports", [ReportController::class, "generateReport"]);
Route::get("member/reports", [MemberController::class, "generateReport"]);
Route::get("technician/reports", [TechnicianController::class, "generateReport"]);

// update the profile picture
Route::post("technician/dp/update", [TechnicianController::class, "upload_dp"])->middleware("authenticate");
Route::post("member/dp/update", [MemberController::class, "upload_dp"])->middleware("authenticate");
Route::post("admin/dp/update", [AdministratorController::class, "upload_dp"])->middleware("authenticate");

// deductions
Route::post("admin/deductions", [DeductionController::class,"getDeductions"])->middleware("authenticate");
Route::post("admin/deductions/delete/{deduction_id}", [DeductionController::class, "deleteDeductions"])->middleware("authenticate");
Route::post("admin/deductions/update", [DeductionController::class, "updateDeductions"])->middleware("authenticate");
Route::post("admin/deductions/add", [DeductionController::class, "addDeduction"])->middleware("authenticate");
Route::post("admin/deductions/update_status", [DeductionController::class, "updateDeductionStatus"])->middleware("authenticate");