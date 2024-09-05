<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Models\SuperAdministrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    // get the administrators
    function getSuperAdministrators(Request $request){
        // authentication_code
        $authentication_code = $request->header("maru-authentication_code");
        
        // credential
        $credential = Credential::where("authentication_code", $authentication_code)->first();
        $super_admin_id = $credential != null ? $credential->user_id : "0";

        // get the administrators
        $super_admin = DB::select("SELECT * FROM `super_administrators` WHERE `user_id` != ? ORDER BY `user_id` DESC", [$super_admin_id]);
        return response()->json(["success" => true, "super_administrators" => $super_admin]);
    }

    // super administrator details
    function superAdministratorDetails($super_admin_id){
        $super_admin = DB::select("SELECT * FROM `super_administrators` WHERE `user_id` = ? ORDER BY `user_id` DESC", [$super_admin_id]);
        if (count($super_admin)) {
            // return response
            return response()->json(["success" => true, "super_admin" => $super_admin[0]]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid super administrator!"]);
        }
    }

    function deleteSuperAdmin($super_admin_id){
        $super_admin = DB::select("SELECT * FROM `super_administrators` WHERE `user_id` = ? ORDER BY `user_id` DESC", [$super_admin_id]);
        if (count($super_admin)) {
            // return response
            $delete = DB::delete("DELETE FROM `super_administrators` WHERE `user_id` = ?", [$super_admin_id]);
            return response()->json(["success" => true, "message" => "Super admin has been deleted successfully!"]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid super administrator!"]);
        }
    }

    function updateSuperAdmin(Request $request){
        // check phone number and id
        $check_phone = DB::select("SELECT * FROM `super_administrators` WHERE `phone_number` = ? AND `user_id` != ?", [$request->input("phone_number"), $request->input("user_id")]);
        if (count($check_phone) > 0) {
            return response()->json(["success" => false, "message" => "Phone number has been used!"]);
        }

        // check id
        $check_id = DB::select("SELECT * FROM `super_administrators` WHERE `national_id` = ? AND `user_id` != ?", [$request->input("national_id"), $request->input("user_id")]);
        if (count($check_id) > 0) {
            return response()->json(["success" => false, "message" => "National id number has been used!"]);
        }

        // update technician
        $super_administrators = SuperAdministrator::find($request->input("user_id"));
        if ($super_administrators) {
            $super_administrators->fullname = $request->input("fullname");
            $super_administrators->phone_number = $request->input("phone_number");
            $super_administrators->email = $request->input("email");
            $super_administrators->residence = $request->input("residence");
            $super_administrators->region = $request->input("region");
            $super_administrators->national_id = $request->input("national_id");
            $super_administrators->gender = $request->input("gender");
            $super_administrators->status = $request->input("status");
            $super_administrators->save();

            return response()->json(["success" => true, "message" => "Super administrator has been updated successfully!"]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid administrator!"]);
        }
    }

    function newSuperAdmin(Request $request){
        // check phone number and id
        $check_phone = DB::select("SELECT * FROM `super_administrators` WHERE `phone_number` = ? AND `user_id` != ?", [$request->input("phone_number"), $request->input("user_id")]);
        if (count($check_phone) > 0) {
            return response()->json(["success" => false, "message" => "Phone number has been used!"]);
        }

        // check id
        $check_id = DB::select("SELECT * FROM `super_administrators` WHERE `national_id` = ? AND `user_id` != ?", [$request->input("national_id"), $request->input("user_id")]);
        if (count($check_id) > 0) {
            return response()->json(["success" => false, "message" => "National id number has been used!"]);
        }

        // check username in credentials
        $check_username = DB::select("SELECT * FROM `credentials` WHERE `username` = ?", [$request->input("username")]);
        if (count($check_username) > 0) {
            return response()->json(["success" => false, "message" => "Username has been taken!"]);
        }

        $super_admin = new SuperAdministrator();
        $super_admin->fullname = $request->input("fullname");
        $super_admin->phone_number = $request->input("phone_number");
        $super_admin->email = $request->input("email");
        $super_admin->residence = $request->input("residence");
        $super_admin->region = $request->input("region");
        $super_admin->national_id = $request->input("national_id");
        $super_admin->gender = $request->input("gender");
        $super_admin->status = $request->input("status");
        $super_admin->username = $request->input("username");
        $super_admin->password = $request->input("password");
        $super_admin->profile_photo = "";
        $super_admin->save();
        
        // register their credentials
        $credential = new Credential();
        $credential->user_id = $super_admin->user_id;
        $credential->username = $request->input("username");
        $credential->password = $request->input("password");
        $credential->user_type = "3";
        $credential->save();

        return response()->json(["success" => true, "message" => "Super Administrator has been added successfully!"]);
    }
}
