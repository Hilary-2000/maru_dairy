<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Models\Technician;
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
                if ($time > 0 && $time < 9) {
                    $time_of_day = "Goodmorning";
                }elseif ($time > 9 && $time < 12) {
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
}
