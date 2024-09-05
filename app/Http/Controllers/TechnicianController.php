<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Credential;
use App\Models\Member;
use App\Models\SuperAdministrator;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

date_default_timezone_set('Africa/Nairobi');

use function PHPUnit\Framework\isEmpty;

class TechnicianController extends Controller
{
    //get technician data
    function getTechnicianData($token){
        // go to the credentials and see who the token belongs to
        $credential = Credential::where("authentication_code", $token)->get();
        if (count($credential) > 0) {
            $technician = Technician::where("user_id", $credential[0]->user_id)->get();
            if (count($technician) > 0) {
                foreach ($technician as $key => $value) {
                    $technician[$key]->fullname = ucwords(strtolower($technician[$key]->fullname));
                }
                // time of day 
                $time = date("H");
                $time_of_day = "Goodmorning";
                if ($time > 0 && $time < 11) {
                    $time_of_day = "Goodmorning";
                }elseif ($time > 11 && $time < 12) {
                    $time_of_day = "Hello";
                }elseif ($time > 12 && $time < 15) {
                    $time_of_day = "Good Afternoon";
                }elseif ($time > 15 && $time < 23) {
                    $time_of_day = "Good Evening";
                }else{
                    $time_of_day = "Hello";
                }
                return response()->json(["success" => true, "data" => $technician, "greetings" => $time_of_day, "token" => $token]);
            }else{
                return response()->json(["success" => false, "message" => "Invalid User! Log-out and try again", "token" => $token]);
            }
        }else{
            return response()->json(["success" => false, "message" => "Login Expired! Log-out and try again", "token" => $token]);
        }
    }

    function getDetails($technician_id){
        // get the credentials
        $credential = DB::select("SELECT * FROM `credentials` WHERE `authentication_code` = ?", [$technician_id]);
        if(count($credential) > 0){
            // technician data
            $technician_data = DB::select("SELECT * FROM `technicians` WHERE `user_id` = ?", [$credential[0]->user_id]);

            // get the collection stats
            $collection_days = DB::select("SELECT COUNT(*) AS 'days_collected' FROM `milk_collections` WHERE `technician_id` = '".$credential[0]->user_id."' AND `user_type` = '1'");
            $collection_amount = DB::select("SELECT `collection_amount` FROM `milk_collections` WHERE `technician_id` = '".$credential[0]->user_id."' AND `user_type` = '1'");
            $total = 0;
            foreach($collection_amount as $collect){
                $total += $collect->collection_amount*=1;
            }
            
            // return the data
            $technician_data[0]->collection_days = count($collection_days) > 0 ? $collection_days[0]->days_collected : 0;
            $technician_data[0]->collection_amount = number_format($total);
            return response()->json(["success" => true, "total_collection" => $total, "technician_data" => $technician_data[0]]);
        }else{
            // return the data
            return response()->json(["success" => false, "message" => "Invalid technician!"]);
        }
    }

    function updateUserDetails(Request $request){
        $fullname = $request->input("fullname");
        $gender = $request->input("gender");
        $phone_number = $request->input("phone_number");
        $email = $request->input("email");
        $residence = $request->input("residence");
        $region = $request->input("region");
        $username = $request->input("username");
        $national_id = $request->input("national_id");

        // token
        $authentication_code = $request->header('maru-authentication_code');
        $check_username = DB::select("SELECT * FROM `credentials` WHERE `username` = ? AND `authentication_code` != ?", [$username, $authentication_code]);
        
        if (count($check_username) == 0) {
            // token
            $token = $request->header("maru-authentication_code");
            $tech = DB::select("SELECT * FROM `credentials` WHERE `authentication_code` = ?", [$token]);
            
            if (count($tech) > 0) {
                // get the technician id
                $technician = Technician::find($tech[0]->user_id);
                $technician->fullname = $fullname;
                $technician->phone_number = $phone_number;
                $technician->gender = $gender;
                $technician->email = $email;
                $technician->residence = $residence;
                $technician->region = $region;
                $technician->username = $username;
                $technician->national_id = $national_id;
                $technician->user_id = $tech[0]->user_id;
                $technician->save();
    
                // update the credential username
                $credential = Credential::find($tech[0]->credential_id);
                $credential->username = $username;
                $credential->save();
                
                // return statement
                return response()->json(["success" => true, "message" => "Update has been successfully done!"]);
            }else{
                return response()->json(["success" => false, "message" => "An error has occured!"]);
            }
        }else{
            return response()->json(["success" => false, "message" => "The username provided has been used!"]);
        }
    }

    function updateCredentials(Request $request){
        // find the technician with the username and password provided
        $username = $request->input("username");
        $password = $request->input("password");

        // get the userdata
        $credential = Credential::where("username", $username)->first();
        if ($credential) {
            // update the credentials
            $credential->password = $password;
            $credential->save();

            // update the technician password
            if ($credential->user_type == "1") {
                $technician = Technician::find($credential->user_id);
                $technician->password = $password;
                $technician->save();
            }elseif ($credential->user_type == "2") {
                $administrator = Administrator::find($credential->user_id);
                $administrator->password = $password;
                $administrator->save();
            }elseif ($credential->user_type == "3") {
                $superAdmin = SuperAdministrator::find($credential->user_id);
                $superAdmin->password = $password;
                $superAdmin->save();
            }elseif ($credential->user_type == "4") {
                $member = Member::find($credential->user_id);
                $member->password = $password;
                $member->save();
            }
            // update the personal credentials
            return response()->json(["success" => true, "message" => "Successfully updated your password!"]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid credential provided!"]);
        }
    }

    // get technicians
    function getTechnicians(){
        // technicians
        $technicians = DB::select("SELECT * FROM `technicians` ORDER BY `user_id` DESC");

        if (count($technicians)) {
            return response()->json(["success" => true, "technicians" => $technicians]);
        }else {
            return response()->json(["success" => false, "message" => "No technicians found!"]);
        }
    }

    function technicianDetails($technician_id){
        // technician data
        $technician_data = DB::select("SELECT * FROM `technicians` WHERE `user_id` = ?", [$technician_id]);
        
        if (count($technician_data) > 0) {
            // get the collection stats
            $collection_days = DB::select("SELECT COUNT(*) AS 'days_collected' FROM `milk_collections` WHERE `technician_id` = '".$technician_id."' AND `user_type` = '1'");
            $collection_amount = DB::select("SELECT `collection_amount` FROM `milk_collections` WHERE `technician_id` = '".$technician_id."' AND `user_type` = '1'");
            

            // total amount
            $total = 0;
            foreach($collection_amount as $collect){
                $total += $collect->collection_amount*=1;
            }
            
            // return the data
            $technician_data[0]->collection_days = count($collection_days) > 0 ? $collection_days[0]->days_collected : 0;
            $technician_data[0]->collection_amount = number_format($total, 2);
            return response()->json(["success" => true, "total_collection" => $total, "technician_data" => $technician_data[0]]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid Technician!"]);
        }
    }

    function updateTechnician(Request $request){
        // check phone number and id
        $check_phone = DB::select("SELECT * FROM `technicians` WHERE `phone_number` = ? AND `user_id` != ?", [$request->input("phone_number"), $request->input("user_id")]);
        if (count($check_phone) > 0) {
            return response()->json(["success" => false, "message" => "Phone number has been used!"]);
        }

        // check id
        $check_id = DB::select("SELECT * FROM `technicians` WHERE `national_id` = ? AND `user_id` != ?", [$request->input("national_id"), $request->input("user_id")]);
        if (count($check_id) > 0) {
            return response()->json(["success" => false, "message" => "National id number has been used!"]);
        }
        // update technician
        $technician = Technician::find($request->input("user_id"));
        if ($technician) {
            $technician->fullname = $request->input("fullname");
            $technician->phone_number = $request->input("phone_number");
            $technician->email = $request->input("email");
            $technician->residence = $request->input("residence");
            $technician->region = $request->input("region");
            $technician->national_id = $request->input("national_id");
            $technician->gender = $request->input("gender");
            $technician->status = $request->input("status");
            $technician->save();

            return response()->json(["success" => true, "message" => "Technician has been updated successfully!"]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid technician!"]);
        }
    }

    function deleteTechnician($technician_id){
        $technician = Technician::find($technician_id);
        if($technician){
            $delete =DB::delete("DELETE FROM `technicians` WHERE `user_id` = '".$technician_id."'");
            return response()->json(["success" => true, "message" => "Technician deleted successfully!"]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid technician!"]);
        }
    }

    // new technician
    function registerTechnician(Request $request){
        // check phone number and id
        $check_phone = DB::select("SELECT * FROM `technicians` WHERE `phone_number` = ?", [$request->input("phone_number")]);
        if (count($check_phone) > 0) {
            return response()->json(["success" => false, "message" => "Phone number has been used!"]);
        }

        // check id
        $check_id = DB::select("SELECT * FROM `technicians` WHERE `national_id` = ?", [$request->input("national_id")]);
        if (count($check_id) > 0) {
            return response()->json(["success" => false, "message" => "National id number has been used!"]);
        }

        // check username in credentials
        $check_username = DB::select("SELECT * FROM `credentials` WHERE `username` = ?", [$request->input("username")]);
        if (count($check_username) > 0) {
            return response()->json(["success" => false, "message" => "Username has been taken!"]);
        }


        // save the technician
        $technician = new Technician();
        $technician->fullname = $request->input("fullname");
        $technician->phone_number = $request->input("phone_number");
        $technician->email = $request->input("email");
        $technician->residence = $request->input("residence");
        $technician->region = $request->input("region");
        $technician->national_id = $request->input("national_id");
        $technician->gender = $request->input("gender");
        $technician->status = $request->input("status");
        $technician->username = $request->input("username");
        $technician->password = $request->input("password");
        $technician->profile_photo = "";
        $technician->save();
        
        // register their credentials
        $credential = new Credential();
        $credential->user_id = $technician->user_id;
        $credential->username = $request->input("username");
        $credential->password = $request->input("password");
        $credential->user_type = "1";
        $credential->save();
        
        // response
        return response()->json(["success" => true, "message" => "Technician registered successfully!"]);
    }
}
