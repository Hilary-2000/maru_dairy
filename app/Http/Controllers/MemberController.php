<?php

namespace App\Http\Controllers;

use App\Models\collectionLogs;
use App\Models\Credential;
use App\Models\Member;
use App\Models\Milk_collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
date_default_timezone_set('Africa/Nairobi');

class MemberController extends Controller
{
    // register member
    function registerMember(Request $request){
        // check if the username is used by anyone
        $check_username = Credential::where("username", $request->input("username"))->first();
        if ($check_username != null) {
            return response()->json(["success" => false, "message" => "Username already used! Login to your account."], 403);
        }

        // check for the national id
        $check_national_id = Member::where("national_id", $request->input("national_id"))->first();
        if ($check_national_id != null) {
            return response()->json(["success" => false, "message" => "National Id already used! Login to your account if you have one."], 403);
        }

        // check for the phone number
        $check_phone_number = Member::where("phone_number", $request->input("phone_number"))->first();
        if ($check_phone_number != null) {
            return response()->json(["success" => false, "message" => "Phone number is already used! Login to your account if you have one."], 403);
        }
        
        // create the member
        $member = new Member();
        $member->fullname = $request->input("fullname");
        $member->gender = $request->input("gender");
        $member->phone_number = $request->input("phone_number");
        $member->email = $request->input("email");
        // $member->residence = $request->input("residence");
        $member->region = $request->input("region");
        $member->username = $request->input("username");
        $member->password = $request->input("password");
        $member->date_registered = date("YmdHis");
        $member->national_id = $request->input("national_id");
        $member->save();

        // create credentials for the member
        $credential = new Credential();
        $credential->user_id = $member->user_id;
        $credential->user_type = 4;
        $credential->username = $member->username;
        $credential->password = $member->password;
        $credential->save();

        return response()->json(["success" => true, "message" => "Account created successfully, Login with you created credentials!"], 200);
    }

    // get new members
    function getMembers(){
        // get the data
        $members = Member::orderBy("user_id", "desc")->limit(500)->get();
        
        // CHECK IF MEMBERS MILK WAS COLLECTED
        foreach ($members as $key => $value) {
            $collection = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = '".$value->user_id."' AND `collection_date` LIKE '".date("Ymd")."%'");
            $members[$key]->collected_today = count($collection) > 0;
        }
        return response()->json(["success" => true, "data" => $members]);
    }

    // get the member data
    function getMemberData($member_id){
        // get the member data
        $member = Member::find($member_id);
        if ($member == null) {
            // No previous milk records found
            return response()->json(['success' => false, "message" => "No previous milk records found!"]);
        }

        // get the last collection history
        $last_collection = DB::select("SELECT * FROM `milk_collections` WHERE member_id = '".$member_id."' ORDER BY `collection_id` DESC;");
        if (count($last_collection)) {
            $last_collection[0]->collection_date = date("D dS M Y @ H:i:s", strtotime($last_collection[0]->collection_date));
            $last_collection[0]->collection_time = date("H:i:s", strtotime($last_collection[0]->collection_date));
        }
        return response()->json(["success" => true, "previous" => $last_collection[0] ?? null, "member" => $member]);
    }

    function uploadMilk(Request $request, $member_id){
        // authentication_code
        $authentication_code = $request->header('maru-authentication_code');

        // check if the authentication code is legit!
        $authenticated = Credential::where("authentication_code", $authentication_code)->first();

        // get the technician id
        if (!$authenticated) {
            return response()->json(["success" => false, "message" => "Authentication failed!"], 401);
        }

        // user id
        $user_id = $authenticated->user_id;
        $user_type = $authenticated->user_type;

        // check if the milk record is today
        $date = date("Ymd");
        $check_milk = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = '".$member_id."' AND `collection_date` LIKE '".$date."%'");
        
        if (count($check_milk) > 0) {
            // update the milk and record all the times it has been changed
            $collection_log = new collectionLogs();
            $collection_log->reading = $request->input("collection_amount");
            $collection_log->user_change = $user_id;
            $collection_log->user_type = $user_type;
            $collection_log->collection_id = $check_milk[0]->collection_id;
            $collection_log->date = date("YmdHis");
            $collection_log->save();

            // update the amount
            $update = DB::update("UPDATE `milk_collections` SET `collection_amount` = ? WHERE `collection_id` = ?", [$request->input("collection_amount"), $check_milk[0]->collection_id]);
        }else{
            // credential
            $collect = new Milk_collection();
            $collect->collection_amount = $request->input("collection_amount");
            $collect->member_id = $member_id;
            $collect->technician_id = $user_id;
            $collect->user_type = $user_type;
            $collect->collection_date = date("YmdHis");
            $collect->save();

            // insert the logs of how many times it has been changes
            $collection_log = new collectionLogs();
            $collection_log->reading = $request->input("collection_amount");
            $collection_log->user_change = $user_id;
            $collection_log->user_type = $user_type;
            $collection_log->collection_id = $collect->collection_id;
            $collection_log->date = date("YmdHis");
            $collection_log->save();
        }

        // return a success message
        return response()->json(["success" => true, "message" => "Saved successfully!"], 200);
    }

    // get the member dashboard
    function getMemberDashboard(Request $request, $period){
        $authentication_code = $request->header("maru-authentication_code");
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
        
        // get the member data
        $member = Credential::where("authentication_code", $authentication_code)->first();
        
        // member
        if($member != null){
            $member_details = Member::find($member->user_id);
            // confirm member details
            if ($member_details != null) {
                // proceed to get the member data
                $data = [];
                $total_collection = 0;
                $collection_status = "constant";
                $percentage = 0;
                $duration = "";
                if($period == "7 days"){
                    $date = date("Ymd");
                    for ($index = 0; $index < 7; $index++) {
                        // collection
                        $collect = DB::select("SELECT SUM(`collection_amount`) AS 'Amount' FROM `milk_collections` WHERE `member_id` = '".$member->user_id."' AND `collection_date` LIKE '".$date."%';");
                        $day_data = array("date" => $date, "label" => date("dS-M", strtotime($date)), "amount" => (count($collect) > 0 ? ($collect[0]->Amount ?? 0) : 0));
                        
                        // day data
                        $total_collection += $day_data['amount'];
                        array_push($data, $day_data);

                        // add the date
                        $date = date("Ymd", strtotime((($index + 1) * -1)." days"));
                    }

                    // previous 7 days collection
                    $start_date = date("Ymd", strtotime("-7 days"))."000000";
                    $end_date = date("Ymd")."235959";
                    $duration = date("D dS M Y", strtotime($start_date))." - ". date("D dS M Y", strtotime($end_date));
                    $start_date = date("Ymd", strtotime("-14 days"))."000000";
                    $end_date = date("Ymd", strtotime("-7 days"))."235959";
                    $previous = DB::select("SELECT SUM(`collection_amount`) AS 'Amount' FROM `milk_collections` WHERE `member_id` = '".$member->user_id."' AND `collection_date` BETWEEN ? AND ? ",[$start_date, $end_date]);
                    $previous_amount = count($previous) > 0 ? $previous[0]->Amount ?? 0 : 0;

                    if ($previous_amount > 0 && $total_collection > 0) {
                        if($previous_amount > $total_collection){
                            $collection_status = "decrease";
                            $percentage = ($previous_amount - $total_collection) / $total_collection * 100;
                        }elseif($previous_amount < $total_collection){
                            $collection_status = "increase";
                            $percentage = ($total_collection - $previous_amount) / $total_collection * 100;
                        }else{
                            $collection_status = "constant";
                            $percentage = 0;
                        }
                    }else{
                        $percentage = 0;
                        $collection_status = "constant";
                    }
                }elseif($period == "14 days"){
                    $counter = 0;
                    for ($index=0; $index < 7; $index++) {
                        // collection
                        $start_date = date("Ymd", strtotime((($counter + 2) * -1)." days"));
                        $end_date = date("Ymd", strtotime(($counter * -1)." days"));
                        $collect = DB::select("SELECT SUM(`collection_amount`) AS 'Amount' FROM `milk_collections` WHERE `member_id` = '".$member->user_id."' AND `collection_date` BETWEEN ? AND ?",[$start_date, $end_date]);
                        $day_data = array("date" => $end_date, "label" => date("dS-M", strtotime($end_date)), "amount" => (count($collect) > 0 ? $collect[0]->Amount ?? 0 : 0));
                        
                        // day data
                        $total_collection += $day_data['amount'];
                        array_push($data, $day_data);

                        // add the date
                        $date = date("Ymd", strtotime($index+1));

                        // add two days
                        $counter+=2;
                    }

                    // previous 14 days collection
                    $start_date = date("Ymd", strtotime("-14 days"))."000000";
                    $end_date = date("Ymd")."235959";
                    $duration = date("D dS M Y", strtotime($start_date))." - ". date("D dS M Y", strtotime($end_date));
                    $start_date = date("Ymd", strtotime("-28 days"))."000000";
                    $end_date = date("Ymd", strtotime("-14 days"))."235959";
                    $previous = DB::select("SELECT SUM(`collection_amount`) AS 'Amount' FROM `milk_collections` WHERE `member_id` = '".$member->user_id."' AND `collection_date` BETWEEN ? AND ? ",[$start_date, $end_date]);
                    $previous_amount = count($previous) > 0 ? $previous[0]->Amount ?? 0 : 0;

                    if ($previous_amount > 0 && $total_collection > 0) {
                        if($previous_amount > $total_collection){
                            $collection_status = "decrease";
                            $percentage = ($previous_amount - $total_collection) / $total_collection * 100;
                        }elseif($previous_amount < $total_collection){
                            $collection_status = "increase";
                            $percentage = ($total_collection - $previous_amount) / $total_collection * 100;
                        }else{
                            $collection_status = "constant";
                            $percentage = 0;
                        }
                    }else{
                        $percentage = 0;
                        $collection_status = "constant";
                    }
                }elseif($period == "30 days"){
                    $counter = 0;
                    for ($index=0; $index < 7; $index++) {
                        // collection
                        $start_date = date("Ymd", strtotime((($counter + 4) * -1)." days"));
                        $end_date = date("Ymd", strtotime(($counter * -1)." days"));
                        $collect = DB::select("SELECT SUM(`collection_amount`) AS 'Amount' FROM `milk_collections` WHERE `member_id` = '".$member->user_id."' AND `collection_date` BETWEEN ? AND ?",[$start_date, $end_date]);
                        $day_data = array("date" => $end_date, "label" => date("dS-M", strtotime($end_date)), "amount" => (count($collect) > 0 ? $collect[0]->Amount ?? 0 : 0));
                        
                        // day data
                        $total_collection += $day_data['amount'];
                        array_push($data, $day_data);

                        // add two days
                        $counter+=4;
                    }

                    // previous 28 days collection
                    $start_date = date("Ymd", strtotime("-28 days"))."000000";
                    $end_date = date("Ymd")."235959";
                    $duration = date("D dS M Y", strtotime($start_date))." - ". date("D dS M Y", strtotime($end_date));
                    $start_date = date("Ymd", strtotime("-56 days"))."000000";
                    $end_date = date("Ymd", strtotime("-28 days"))."235959";
                    $previous = DB::select("SELECT SUM(`collection_amount`) AS 'Amount' FROM `milk_collections` WHERE `member_id` = '".$member->user_id."' AND `collection_date` BETWEEN ? AND ? ",[$start_date, $end_date]);
                    $previous_amount = count($previous) > 0 ? $previous[0]->Amount ?? 0 : 0;

                    if ($previous_amount > 0 && $total_collection > 0) {
                        if($previous_amount > $total_collection){
                            $collection_status = "decrease";
                            $percentage = ($previous_amount - $total_collection) / $total_collection * 100;
                        }elseif($previous_amount < $total_collection){
                            $collection_status = "increase";
                            $percentage = ($total_collection - $previous_amount) / $total_collection * 100;
                        }else{
                            $collection_status = "constant";
                            $percentage = 0;
                        }
                    }else{
                        $percentage = 0;
                        $collection_status = "constant";
                    }
                }elseif($period == "60 days"){
                    $counter = 0;
                    for ($index=0; $index < 7; $index++) {
                        // collection
                        $start_date = date("Ymd", strtotime((($counter + 7) * -1)." days"));
                        $end_date = date("Ymd", strtotime(($counter * -1)." days"));
                        $collect = DB::select("SELECT SUM(`collection_amount`) AS 'Amount' FROM `milk_collections` WHERE `member_id` = '".$member->user_id."' AND `collection_date` BETWEEN ? AND ?",[$start_date, $end_date]);
                        $day_data = array("date" => $end_date, "label" => date("dS-M", strtotime($end_date)), "amount" => (count($collect) > 0 ? $collect[0]->Amount ?? 0 : 0));
                        
                        // day data
                        $total_collection += $day_data['amount'];
                        array_push($data, $day_data);

                        // add two days
                        $counter+=7;
                    }

                    // previous 56 days collection
                    $start_date = date("Ymd", strtotime("-56 days"))."000000";
                    $end_date = date("Ymd")."235959";
                    $duration = date("D dS M Y", strtotime($start_date))." - ". date("D dS M Y", strtotime($end_date));
                    
                    $start_date = date("Ymd", strtotime("-112 days"))."000000";
                    $end_date = date("Ymd", strtotime("-56 days"))."235959";
                    $previous = DB::select("SELECT SUM(`collection_amount`) AS 'Amount' FROM `milk_collections` WHERE `member_id` = '".$member->user_id."' AND `collection_date` BETWEEN ? AND ? ",[$start_date, $end_date]);
                    $previous_amount = count($previous) > 0 ? $previous[0]->Amount ?? 0 : 0;

                    if ($previous_amount > 0 && $total_collection > 0) {
                        if($previous_amount > $total_collection){
                            $collection_status = "decrease";
                            $percentage = ($previous_amount - $total_collection) / $total_collection * 100;
                        }elseif($previous_amount < $total_collection){
                            $collection_status = "increase";
                            $percentage = ($total_collection - $previous_amount) / $total_collection * 100;
                        }else{
                            $collection_status = "constant";
                            $percentage = 0;
                        }
                    }else{
                        $percentage = 0;
                        $collection_status = "constant";
                    }
                }
                
                // return data
                return response()->json(["success" => true, "previous_amount" => $previous_amount, "greetings" => $time_of_day,"duration" => $duration , "percentage" => round($percentage, 0) ,"collection_status" => $collection_status,  "total_collection" => $total_collection, "collection_data" => $data, "member_details" => $member_details]);
            }else{
                // return the error message
                return response()->json(["success" => false, "message" => "Invalid user!"]);
            }
        }else{
            return response()->json(["success" => false, "message" => "Invalid user!"]);
        }
    }

    function getMemberHistory(Request $request){
        // authentication_code
        $authentication_code = $request->header("maru-authentication_code");
        
        // get the member data
        $member = Credential::where("authentication_code", $authentication_code)->first();
        
        // get the member history
        if($member){
            // member
            $start_date = date("Ymd", strtotime("-30 days"))."000000";
            $end_date = date("Ymd")."235959";
            $collection_history = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = ? AND `collection_date` BETWEEN ? AND ? ORDER BY `collection_id` DESC", [$member->user_id, $start_date, $end_date]);
            $count = DB::select("SELECT SUM(collection_amount) AS 'total' FROM `milk_collections` WHERE `member_id` = ? AND `collection_date` BETWEEN ? AND ?", [$member->user_id, $start_date, $end_date]);
            
            // collection history iteration
            foreach ($collection_history as $key => $value) {
                $collection_history[$key]->date = date("D dS M Y", strtotime($value->collection_date));
                $collection_history[$key]->time = date("h:iA", strtotime($value->collection_date));
            }

            // collection history
            return response()->json(["success" => true, "count" => number_format(count($count) > 0 ? ($count[0]->total ?? 0) : 0), "collection_history" => $collection_history]);
        }else{
            // collection history
            return response()->json(["success" => false, "message" => "An error has occured!"]);
        }
    }

    function getMilkDetails($collection_id){
        $milk_details = DB::select("SELECT * FROM `milk_collections` WHERE `collection_id` = ?", [$collection_id]);
        if (count($milk_details) > 0) {
            $milk_details[0]->date = date("D, dS M Y", strtotime($milk_details[0]->collection_date));
            $milk_details[0]->time = date("h:iA", strtotime($milk_details[0]->collection_date));
            return response()->json(["success" => true, "milk_details" => $milk_details[0]]);
        }else{
            return response()->json(["success" => false, "message" => "Collection details not found, It could be deleted!"]);
        }
    }

    function changeMilkStatus(Request $request, $milk_id){
        $status = $request->input("status");
        $milk_status = DB::update("UPDATE `milk_collections` SET `collection_status` = ? WHERE `collection_id` = ?", [$status, $milk_id]);
        return response()->json(["success" => true, "message" => "Update has been done successfully!"]);
    }
    
    // view profile
    function viewProfile(Request $request){
        // authentication_code
        $authentication_code = $request->header("maru-authentication_code");

        // check if the authentication code is legit!
        $authenticated = Credential::where("authentication_code", $authentication_code)->first();

        // authenticated
        if ($authenticated) {
            $member_details = Member::find($authenticated->user_id);
            $collection_days = DB::select("SELECT COUNT(*) AS 'total' FROM `milk_collections` WHERE `member_id` = ?",[$member_details->user_id]);
            $total_collection = DB::select("SELECT SUM(`collection_amount`) AS 'total' FROM `milk_collections` WHERE `member_id` = ?", [$member_details->user_id]);
            
            // return value
            return response()->json(["success" => true, "collection_days" => number_format(count($collection_days) > 0 ? $collection_days[0]->total ?? 0 : 0), "total_collection" => number_format(count($total_collection) > 0 ? $total_collection[0]->total ?? 0 : 0), "member_details" => $member_details]);
        }else{
            // return value
            return response()->json(["success" => false, "message" => "Invalid token, Login and try again!"]);
        }
    }

    // update the member
    function updateMember(Request $request){

        // authentication_code
        $authentication_code = $request->header("maru-authentication_code");

        // fullname
        $fullname = $request->input("fullname");
        $phone_number = $request->input("phone_number");
        $gender = $request->input("gender");
        $email = $request->input("email");
        $residence = $request->input("residence");
        $region = $request->input("region");

        // update

        // check if the authentication code is legit!
        $authenticated = Credential::where("authentication_code", $authentication_code)->first();

        // authenticated
        if ($authenticated) {
            $member_details = Member::find($authenticated->user_id);
            if ($member_details != null) {
                $member_details->fullname = $fullname;
                $member_details->gender = $gender;
                $member_details->phone_number = $phone_number;
                $member_details->email = $email;
                $member_details->residence = $residence;
                $member_details->region = $region;
                $member_details->user_id = $authenticated->user_id;
                $member_details->save();

                // return value
                return response()->json(["success" => true, "message" => "Changes saved successfully!"]);
            }else{
                // return value
                return response()->json(["success" => false, "message" => "Invalid token, Login and try again!"]);
            }
        }else{
            // return value
            return response()->json(["success" => false, "message" => "Invalid token, Login and try again!"]);
        }
    }
}
