<?php

namespace App\Http\Controllers;

use App\Classes\reports\PDF;
use App\Models\collectionLogs;
use App\Models\Credential;
use App\Models\Deduction;
use App\Models\Member;
use App\Models\Milk_collection;
use App\Models\Payment;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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
                    $duration = date("dS M Y", strtotime($start_date))." - ". date("dS M Y", strtotime($end_date));
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
                    $duration = date("dS M Y", strtotime($start_date))." - ". date("dS M Y", strtotime($end_date));
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
                    $duration = date("dS M Y", strtotime($start_date))." - ". date("dS M Y", strtotime($end_date));
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
                    $duration = date("dS M Y", strtotime($start_date))." - ". date("dS M Y", strtotime($end_date));
                    
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
                return response()->json(["success" => true, "previous_amount" => $previous_amount, "greetings" => $time_of_day,"duration" => $duration , "percentage" => round($percentage, 0) ,"collection_status" => $collection_status,  "total_collection" => round($total_collection, 2), "collection_data" => $data, "member_details" => $member_details]);
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

            $total_amount = $this->getTotalPrice($collection_history);

            // collection history
            return response()->json(["success" => true, "total_amount" => number_format($total_amount, 2), "count" => number_format((count($count) > 0 ? ($count[0]->total ?? 0) : 0), 2), "collection_history" => $collection_history]);
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
            $total_price = $this->getTotalPrice($milk_details);
            $milk_details[0]->price = number_format($total_price, 2);
            $milk_details[0]->ppl = number_format($total_price/$milk_details[0]->collection_amount, 2);
            return response()->json(["success" => true, "milk_details" => $milk_details[0]]);
        }else{
            return response()->json(["success" => false, "message" => "Collection details not found, It could be deleted!"]);
        }
    }

    function getTotalPrice($data){
        $total_price = 0;
        foreach ($data as $key => $value) {
            $collection_date = date("Ymd", strtotime($value->collection_date))."000000";
            $collection_amount = $value->collection_amount;

            // get the milk price
            $select = DB::select("SELECT * FROM `milk_prices` WHERE `effect_date` < '".$collection_date."' AND `status` = '1' ORDER BY `price_id` DESC LIMIT 1");
            $total_price += (count($select) > 0 ? $select[0]->amount : 0) * $collection_amount;
        }

        // return price
        return $total_price;
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
            if ($member_details) {
                $collection_days = DB::select("SELECT COUNT(*) AS 'total' FROM `milk_collections` WHERE `member_id` = ?",[$member_details->user_id]);
                $total_collection = DB::select("SELECT SUM(`collection_amount`) AS 'total' FROM `milk_collections` WHERE `member_id` = ?", [$member_details->user_id]);
                
                // return value
                return response()->json(["success" => true, "collection_days" => number_format(count($collection_days) > 0 ? $collection_days[0]->total ?? 0 : 0), "total_collection" => number_format(count($total_collection) > 0 ? $total_collection[0]->total ?? 0 : 0), "member_details" => $member_details]);
            }else{
                // return value
                return response()->json(["success" => false, "message" => "Invalid member!"]);
            }
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

    // delete member
    function deleteMember($member_id){
        $member = DB::select("SELECT * FROM `members` WHERE `user_id` = ?", [$member_id]);
        if (count($member) > 0) {
            // delete the member
            $member_id = DB::delete("DELETE FROM `members` WHERE `user_id` = ?", [$member_id]);
            
            // delete the collection
            $delete_collection = DB::delete("DELETE FROM `milk_collections` WHERE `member_id` = ?", [$member_id]);

            // return response
            return response()->json(["success" => true, "message" => ucwords(strtolower($member[0]->fullname))." has been deleted successfully!"]);
        }else{
            return response()->json(["success" => false, "message" => "Member not found, maybe they have been deleted!"]);
        }
    }

    // membership
    function memberMembership($member_id){
        $member_details = Member::find($member_id);
        if($member_details){
            // set the member date
            $member_details->date_joined = date("D dS M Y", strtotime($member_details->date_registered));

            // get last month`s collection amount
            $last_month = date("Ym", strtotime("-1 month"));
            $current_month = date("Ym");
            
            $admin = new AdministratorController();
            // last months collections
            $start = $last_month."01000000";
            $end = $last_month."31000000";
            $last_month_collection = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = ? AND `collection_date` BETWEEN ? AND ?", [$member_id, $start, $end]);
            $last_month_pay = $admin->getTotalPrice($last_month_collection);
            $last_month_litres = $admin->getTotalLitres($last_month_collection);

            // current months collections
            $start = $current_month."01000000";
            $end = $current_month."31000000";
            $current_month_collection = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = ? AND `collection_date` BETWEEN ? AND ?", [$member_id, $start, $end]);
            $current_month_pay = $admin->getTotalPrice($current_month_collection);
            $current_month_litres = $admin->getTotalLitres($current_month_collection);
            
            // joining fees
            $joining_fees = $this->getJoiningFees($member_details->date_registered);
            $deduction_paid = DB::select("SELECT SUM(`deduction_amount`) AS 'total' FROM `deductions` WHERE `deduction_type` = 'joining_fees' AND `member_id` = '".$member_id."'");
            $joining_fees_balance = $joining_fees - ($deduction_paid[0]->total ?? 0);

            // membership balance
            $membership_amount = $member_details->membership_fees;
            $membership_amount_paid = DB::select("SELECT SUM(`deduction_amount`) AS 'total' FROM `deductions` WHERE `deduction_type` = 'membership_fees' AND `member_id` = '".$member_id."'");
            $membership_amount_balance = $membership_amount - ($membership_amount_paid[0]->total ?? 0);

            // annual subscription
            $annual_subscription_balance = $this->annual_subscription_balance($member_id);
            
            // annual subscription and payment
            $annual_sub_n_payment = $this->annual_subscription_and_payment($member_id);

            // payments
            $monthly_payments = DB::select("SELECT * FROM `payments` WHERE `member_id` = ? ORDER BY `payment_id` DESC", [$member_id]);
            
            $date_joined = date("Ym");
            $counter = 100;
            $earnings = [];
            while(true){
                $date_joined = strlen($date_joined) > 6 ? date("Ym", strtotime($date_joined."-01")) : $date_joined;
                $start = $date_joined."01000000";
                $end = $this->addMonths($date_joined."01", 1)."000000";
                
                // collection
                $collection = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = ? AND `collection_date` BETWEEN ? AND ?", [$member_details->user_id, $start, $end]);
                $collection_amount = $admin->getTotalPrice($collection);
                $litres_collected = $admin->getTotalLitres($collection);

                $confirmed = false;
                $payment_id = null;
                $payment_amount = 0;
                foreach ($monthly_payments as $key => $value) {
                    if ($value->month_paid_for == date("M-Y", strtotime($start))) {
                        $confirmed = true;
                        $payment_id = $value->payment_id;
                        $payment_amount = $value->payment_amount;
                    }
                }

                // earning data
                $earn = array("id" => $payment_id, "confirmed" => $confirmed, "month_paid_for" => date("M-Y", strtotime($start)), "raw_start_date" => $start, "litres_collected" => $litres_collected, "raw_end_date" => $end, "raw_amount" => $confirmed ? $payment_amount : $collection_amount, "payment_amount" => $confirmed ? number_format($payment_amount, 2) : number_format($collection_amount, 2), "start" => date("dS M Y", strtotime($start)), "end" => date("dS M Y", strtotime($this-> addDate("$end-01", '-1 days'))), "publish_date" => date("dS M Y", strtotime($end)), "transaction_cost" => $this->mpesa_transaction_cost($collection_amount));
                array_push($earnings, $earn);

                // check what was earned every month
                if(date("Y-m", strtotime($start)) == date("Y-m", strtotime($member_details->date_registered)) || $counter == 0){
                    break;
                }

                // date joined
                $date_joined = date("Y-m", strtotime($this->addMonths($start."-01", -1)));
                $counter--;
            }

            return response()->json(
                [
                    "success" => true,
                    "last_month_litres" => $last_month_litres,
                    "current_month_litres" => $current_month_litres, 
                    "member_details" => $member_details,
                    "last_month" => date("M-Y" ,strtotime($last_month."01")),
                    "curr_month" => date("M-Y" ,strtotime($current_month."01")),
                    "last_month_pay" => number_format($last_month_pay, 2),
                    "current_month_pay" => number_format($current_month_pay, 2),
                    "joining_fees_balance" => number_format($joining_fees_balance, 2),
                    "membership_amount_balance" => number_format($membership_amount_balance, 2),
                    "annual_subscription_balance" => number_format($annual_subscription_balance, 2),
                    "annual_sub_n_payment" => $annual_sub_n_payment,
                    "monthly_payments" => $earnings
                ]
            );
        }else{
            return response()->json(["success" => false, "message" => "Member not found, maybe deleted!"]);
        }
    }

    function getJoiningFees($date_joined){
        $month = date("m", strtotime($date_joined));
        if ($month >= 1 && $month <= 3) {
            return 1000;
        }elseif ($month >= 4 && $month <= 6) {
            return 750;
        }elseif ($month >= 7 && $month <= 9) {
            return 500;
        }elseif ($month >= 10 && $month <= 12) {
            return 250;
        }else{
            return 1000;
        }
    }

    function addDate($date, $interval) {
        $dateTime = new DateTime($date);
        $dateTime->modify($interval);
        return $dateTime->format('Ymd');
    }

    function addMonths($date, $months) {
        $dateTime = new DateTime($date);
    
        // Store the original day
        $originalDay = $dateTime->format('d');
    
        // Modify the date by adding months
        $dateTime->modify("+{$months} months");
    
        // Check if the day has changed (overflow occurred)
        if ($dateTime->format('d') !== $originalDay) {
            // Go back to the last day of the previous month
            $dateTime->modify('last day of previous month');
        }
    
        return $dateTime->format('Ymd');
    }
    
    function annual_subscription_balance($member_id){
        $member = Member::find($member_id);
        if ($member) {
            $date_joined = date("Y", strtotime($member->date_registered));
            $current_year = date("Y");

            // return current year
            $annual_subscription = ($current_year - $date_joined) * 1000;

            // amount paid on behalf of the subscription fees
            $annual_subscription_paid = DB::select("SELECT SUM(`deduction_amount`) AS 'total' FROM `deductions` WHERE `deduction_type` = 'subscription' AND `member_id` = '".$member_id."'");

            // balance
            $annual_subscription_balance = $annual_subscription - ($annual_subscription_paid[0]->total ?? 0);

            return $annual_subscription_balance;
        }else{
            return 0;
        }
    }

    function annual_subscription_and_payment($member_id){
        $member = Member::find($member_id);
        if($member){
            $current_year = date("Y");
            $year_joined = date("Y",strtotime($member->date_registered));

            // current_year
            if ($current_year != $year_joined) {
                // loop through the year
                $subscriptions = [];
                for ($year = $current_year; $year >= $year_joined; $year--) {
                    $data = [];
                    // period
                    $start = $year."0101000000";
                    $end = $year."1231235959";
    
                    // subscription payments
                    $deduction = DB::select("SELECT * FROM `deductions` WHERE `deduction_type` = 'membership_fees',  `member_id` = ? AND `deduction_date` BETWEEN ? AND ? ORDER BY `id` DESC", [$member_id, $start, $end]);
    
                    // new annual payment
                    $annual_subscription = array("id" => "-1", "deduction_type" => "increase", "deduction_amount" => "1000", "balance" => "1000", "member_id" => $member_id, "deduction_date" => $year."0101000000", "clear_date" => date("dS M Y", strtotime($year."0101000000")));
                    array_push($data, $annual_subscription);
    
                    // modify the deduction balance
                    foreach ($deduction as $key => $value) {
                        $deduction[$key]->balance = ($annual_subscription['deduction_amount']*1) - $value->deduction_amount;
                        $deduction[$key]->clear_date = date("dS M Y", strtotime($deduction[$key]->deduction_date));
                    }
    
                    // merge annual payment and its deductions
                    $new_data = array_merge($deduction,$data);
                    
                    // push_data
                    $push_data = array("year" => $year, "subscription" => $new_data);
                    array_push($subscriptions,$push_data);
                }

                // return subscriptions
                return $subscriptions;
            }else{
                return [];
            }
        }else{
            return [];
        }
    }

    function paySubscription(Request $request){
        // balance
        $balance = $this->annual_subscription_balance($request->input("member_id"));
        $balance -= $request->input("deduction_amount")*1;

        // subscription
        $pay_subscription = new Deduction();
        $pay_subscription->deduction_type = $request->input("deduction_type");
        $pay_subscription->deduction_amount = $request->input("deduction_amount");
        $pay_subscription->balance = $balance;
        $pay_subscription->member_id = $request->input("member_id");
        $pay_subscription->deduction_date = date("YmdHis");
        $pay_subscription->save();
        
        // response
        return response()->json(["success" => true, "message" => "Payment has been made successfully!"]);
    }

    function mpesa_transaction_cost($amount){
        $transaction_cost = 0;
        if($amount > 100 && $amount <= 500 ){
            $transaction_cost = 7;
        }elseif($amount > 500 && $amount <= 1000){
            $transaction_cost = 13;
        }elseif($amount > 1000 && $amount <= 1500){
            $transaction_cost = 23;
        }elseif($amount > 1500 && $amount <= 2500){
            $transaction_cost = 33;
        }elseif($amount > 2500 && $amount <= 3500){
            $transaction_cost = 53;
        }elseif($amount > 3500 && $amount <= 5000){
            $transaction_cost = 57;
        }elseif($amount > 5000 && $amount <= 7500){
            $transaction_cost = 78;
        }elseif($amount > 7500 && $amount <= 10000){
            $transaction_cost = 90;
        }elseif($amount > 10000 && $amount <= 15000){
            $transaction_cost = 100;
        }elseif($amount > 15000 && $amount <= 20000){
            $transaction_cost = 105;
        }elseif($amount > 20000 && $amount <= 35000){
            $transaction_cost = 108;
        }elseif($amount > 35000 && $amount <= 50000){
            $transaction_cost = 108;
        }elseif($amount > 50000 && $amount <= 250000){
            $transaction_cost = 108;
        }else{
            $transaction_cost = 0;
        }

        return $transaction_cost;
    }

    function acceptMemberPayment(Request $request){
        // add deduction if there is any

        // subscription
        $deductions = $request->input("deductions");
        

        // payment amounts
        $payment = new Payment();
        $payment->payment_amount = $request->input("payment_amount");
        $payment->transaction_cost = $request->input("transaction_cost");
        $payment->date_paid = date("YmdHis");
        $payment->member_id = $request->input("member_id");
        $payment->month_paid_for = $request->input("month_paid_for");
        $payment->litres_amount = $request->input("litres_amount");
        $payment->save();

        $payment_id = $payment->payment_id;
        if(count($deductions) > 0){
            foreach ($deductions as $key => $deduction) {
                $pay_subscription = new Deduction();
                $pay_subscription->deduction_type = $deduction['deduction_type'];
                $pay_subscription->deduction_amount = $deduction['deduction_amount'];
                $pay_subscription->balance = 0;
                $pay_subscription->payment_id = $payment_id;
                $pay_subscription->member_id = $request->input("member_id");
                $pay_subscription->deduction_date = date("YmdHis");
                $pay_subscription->save();
            }
        }

        return response()->json(["success" => true, "message" => "Payment confirmed successfully!"]);
    }

    function declinePayment($payment_id){

        $find_payment = DB::select("SELECT * FROM `payments` WHERE `payment_id` = '".$payment_id."'");
        if (count($find_payment) > 0) {
            // delete deduction
            $delete = DB::select("DELETE FROM `deductions` WHERE `payment_id` = '".$payment_id."'");

            // delete payment
            $delete = DB::delete("DELETE FROM `payments` WHERE `payment_id` = '".$payment_id."'");
            return response()->json(["success" => true, "message" => "Payment deleted successfully!"]);
        }else{
            return response()->json(["success" => false, "message" => "An error has occured!"]);
        }
    }
    
    function generateReport(Request $request){
        // pass data
        $report_type = $request->input("report_type");
        $member_id = $request->input("member_id");
        $start_date = $request->input("start_date")."000000";
        $end_date = $request->input("end_date")."235959";


        if($report_type == "collections"){
            $collection = DB::select("SELECT MC.*, M.fullname AS member_fullname, M.membership FROM `milk_collections` AS MC
                            LEFT JOIN members AS M 
                            ON MC.member_id = M.user_id 
                            WHERE `member_id` = '".$member_id."' AND `collection_date` BETWEEN '".$start_date."' AND '".$end_date."' 
                            ORDER BY `collection_id` DESC");
            

            // group_collection
            $group_collection = [];
            $total_cost = 0;
            $total_litres = 0;
            if (count($collection) > 0) {
                foreach ($collection as $key => $value) {
                    $collection_date = date("Ymd", strtotime($value->collection_date))."235959";
                    $collection_amount = $value->collection_amount;
                    
                    // get the milk price
                    $select = DB::select("SELECT * FROM `milk_prices` WHERE `effect_date` < '".$collection_date."' AND `status` = '1' ORDER BY `price_id` DESC LIMIT 1");
                    $price = (count($select) > 0 ? $select[0]->amount : 0) * $collection_amount;
                    $total_cost += $price;
                    $total_litres += $collection_amount;
                    $value->price = number_format($price, 2);
                    $value->ppl = number_format((count($select) > 0 ? $select[0]->amount : 0), 2);
                    
                    if (isset($group_collection[substr($value->collection_date,0,8)])) {
                        array_push($group_collection[substr($value->collection_date,0,8)]['collection'], $value);
                    }else{
                        $group_collection[substr($value->collection_date,0,8)] = array(
                            "date" => substr($value->collection_date,0,8),
                            "fulldate" => date("D dS M Y", strtotime(substr($value->collection_date,0,8))),
                            "collection" => [$value]
                        );
                    }
                }
            }
            
            $pdf = new PDF("P","mm","A4");
            $pdf->set_document_title("Collection between ".date("D dS M Y", strtotime($start_date))." to ".date("D dS M Y", strtotime($end_date)));
            $pdf->AddPage();
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetMargins(10, 5);
            $pdf->AddFont("robotomonoa",'','RobotoMono-Regular.php');
            $pdf->AddFont("robotomonob",'','RobotoMono-Bold.php');
            $pdf->Ln();
            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(40, 10, "Total Payment:", 1, 0);
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->Cell(40, 10, "Kes ".number_format($total_cost, 2), 1, 1);

            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(40, 10, "Litres Collected:", 1, 0);
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->Cell(40, 10, number_format($total_litres, 2)." Litres", 1, 1);
            $pdf->Ln();
            foreach ($group_collection as $key => $value) {
                $pdf->SetFont('robotomonob', 'U', 10);
                $pdf->Cell(200,10,"Collections for : ".date("D dS M Y", strtotime($key)),0,1, "C");

                // table header
                $pdf->SetFont('robotomonob', '', 10);
                $pdf->Cell(15,10, "#", 1, 0, "C");
                $pdf->Cell(20,10, "Litres", 1, 0, "C");
                $pdf->Cell(35,10, "Price", 1, 0, "C");
                $pdf->Cell(50,10, "Price/L", 1, 0, "C");
                $pdf->Cell(25,10, "Time.", 1, 0, "C");
                $pdf->Cell(40,10, "Membeship No.", 1, 1, "C");
                $pdf->SetFont('robotomonoa', '', 10);
                $total_litres = 0;
                $total_collection = 0;
                foreach ($value['collection'] as $keyed => $valued) {
                    $pdf->Cell(15,6, ($keyed + 1).".", 1, 0, "L");
                    $pdf->Cell(20,6, $valued->collection_amount, 1, 0, "L");
                    $pdf->Cell(35,6, "Kes ".$valued->price, 1, 0, "L");
                    $pdf->Cell(50,6, $valued->ppl, 1, 0, "L");
                    $pdf->Cell(25,6, date("H:i:sA", strtotime($valued->collection_date)), 1, 0, "L");
                    $pdf->Cell(40,6, $valued->membership, 1, 1, "L");

                    // total litres
                    $total_litres += (str_replace(",","",$valued->collection_amount)*1);
                    $total_collection += (str_replace(",","",$valued->price)*1);
                }
                $pdf->SetFont('robotomonob', '', 10);
                $pdf->Cell(15,6, "Total", 1, 0, "L");
                $pdf->Cell(20,6, $total_litres, 1, 0, "L");
                $pdf->Cell(35,6, "Kes ".number_format($total_collection), 1, 0, "L");
                $pdf->Ln(10);
            }
            $pdf->Output();
        }elseif ($report_type == "payments"){
            $members = DB::select("SELECT * FROM `members` WHERE `user_id` = '".$member_id."' ORDER BY `user_id` DESC");
            $total_cost = 0;
            $total_litres = 0;
            foreach ($members as $key => $member) {
                $collection = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = ? AND `collection_date` BETWEEN ? AND ?", [$member->user_id, $start_date, $end_date]);
                $price_collection = $this->getPrice($collection);
                $total_cost += $price_collection[0];
                $total_litres += $price_collection[1];
                $members[$key]->total_price = $price_collection[0];
                $members[$key]->total_litres = $price_collection[1];
                $members[$key]->collection_days = count($collection);
            }
            
            // get members
            if(count($members) > 0){
                $pdf = new PDF("P","mm","A4");
                $pdf->set_document_title("Collection between ".date("D dS M Y", strtotime($start_date))." to ".date("D dS M Y", strtotime($end_date)));
                $pdf->AddPage();
                $pdf->SetFont('Times', 'B', 10);
                $pdf->SetMargins(10, 5);
                $pdf->AddFont("robotomonoa",'','RobotoMono-Regular.php');
                $pdf->AddFont("robotomonob",'','RobotoMono-Bold.php');
                $pdf->Ln();

                $not_confirmed = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = ? AND `collection_status` = '0' AND `collection_date` BETWEEN ? AND ?", [$member->user_id, $start_date, $end_date]);
                $accepted = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = ? AND `collection_status` = '1' AND `collection_date` BETWEEN ? AND ?", [$member->user_id, $start_date, $end_date]);
                $declined = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = ? AND `collection_status` = '2' AND `collection_date` BETWEEN ? AND ?", [$member->user_id, $start_date, $end_date]);
    
    
                $pdf->SetFont('robotomonob', '', 10);
                $pdf->Cell(40, 10, "Fullname:", 1, 0);
                $pdf->SetFont('robotomonoa', "", 10);
                $pdf->Cell(80, 10, ucwords(strtolower($members[0]->fullname)), 1, 1);

                $pdf->SetFont('robotomonob', '', 10);
                $pdf->Cell(40, 10, "Membership:", 1, 0);
                $pdf->SetFont('robotomonoa', "", 10);
                $pdf->Cell(80, 10, (($members[0]->membership)), 1, 1);

                $pdf->SetFont('robotomonob', '', 10);
                $pdf->Cell(40, 10, "Not Confirmed:", 1, 0);
                $pdf->SetFont('robotomonoa', "", 10);
                $pdf->Cell(80, 10, count($not_confirmed)." Collection(s)", 1, 1);

                $pdf->SetFont('robotomonob', '', 10);
                $pdf->Cell(40, 10, "Accepted:", 1, 0);
                $pdf->SetFont('robotomonoa', "", 10);
                $pdf->Cell(80, 10, count($accepted)." Collection(s)", 1, 1);

                $pdf->SetFont('robotomonob', '', 10);
                $pdf->Cell(40, 10, "Declined:", 1, 0);
                $pdf->SetFont('robotomonoa', "", 10);
                $pdf->Cell(80, 10, count($declined)." Collection(s)", 1, 1);
    
                $pdf->SetFont('robotomonob', '', 10);
                $pdf->Cell(40, 10, "Total Payment:", 1, 0);
                $pdf->SetFont('robotomonoa', '', 10);
                $pdf->Cell(80, 10, "Kes ".number_format($total_cost, 2), 1, 1);
    
                $pdf->SetFont('robotomonob', '', 10);
                $pdf->Cell(40, 10, "Litres Collected:", 1, 0);
                $pdf->SetFont('robotomonoa', '', 10);
                $pdf->Cell(80, 10, number_format($total_litres, 2)." Litres", 1, 1);
    
                // table header
                $pdf->Ln();
                $pdf->SetFont('robotomonob', 'U', 10);
                $pdf->Cell(200,10, "Collections and payments", 0, 1, "C");
                $pdf->SetFont('robotomonob', '', 9);
                $pdf->Cell(10,8, "#", 1, 0, "C");
                $pdf->Cell(55,8, "Member", 1, 0, "C");
                $pdf->Cell(35,8, "Membership", 1, 0, "C");
                $pdf->Cell(35,8, "Litres", 1, 0, "C");
                $pdf->Cell(30,8, "Price", 1, 0, "C");
                $pdf->Cell(30,8, "Records", 1, 1, "C");
                $pdf->SetFont('robotomonoa', '', 10);
                foreach ($members as $key => $member) {
                    $pdf->Cell(10,7, ($key+1).".", 1, 0, "L");
                    $pdf->Cell(55,7, ucwords(strtolower($member->fullname)), 1, 0, "L");
                    $pdf->Cell(35,7, $member->membership, 1, 0, "L");
                    $pdf->Cell(35,7, number_format($member->total_litres, 2), 1, 0, "L");
                    $pdf->Cell(30,7, "Kes ".number_format($member->total_price), 1, 0, "L");
                    $pdf->Cell(30,7, $member->collection_days, 1, 1, "L");
                }
                $pdf->SetFont('robotomonob', '', 10);
                $pdf->Cell(65,7, "", 0, 0, "L");
                $pdf->Cell(35,7, "Total", 1, 0, "L");
                $pdf->Cell(35,7, number_format($total_litres, 2), 1, 0, "L");
                $pdf->Cell(30,7, "Kes ".number_format($total_cost, 2), 1, 1, "L");
                $pdf->Output();
            }else{
                return response()->json(["success" => false, "message" => "Invalid member!"]);
            }
        }else{
            $pdf = new PDF("P","mm","A4");
            $pdf->set_document_title("Collection between ".date("D dS M Y", strtotime($start_date))." to ".date("D dS M Y", strtotime($end_date)));
            $pdf->AddPage();
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetMargins(10, 5);
            $pdf->AddFont("robotomonoa",'','RobotoMono-Regular.php');
            $pdf->AddFont("robotomonob",'','RobotoMono-Bold.php');
            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(190, 10, "No options selected", 1, 0,"C");
            $pdf->SetFont('robotomonoa', "", 10);
            $pdf->Output();
        }
    }
    function getPrice($data){
        $total_price = 0;
        $total_litres = 0;
        foreach ($data as $key => $value) {
            $collection_date = date("Ymd", strtotime($value->collection_date))."000000";
            $collection_amount = $value->collection_amount;
            $total_litres += $value->collection_amount;

            // get the milk price
            $select = DB::select("SELECT * FROM `milk_prices` WHERE `effect_date` < '".$collection_date."' AND `status` = '1' ORDER BY `price_id` DESC LIMIT 1");
            $total_price += (count($select) > 0 ? $select[0]->amount : 0) * $collection_amount;
        }

        // return price
        return [$total_price, $total_litres];
    }

    function upload_dp(Request $req)
    {
        // Validate the request
        $req->validate([
            'mine_dp' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'user_id' => 'required|integer'
        ]);
    
        // Set variables
        $user_id = $req->input('user_id');
        $imageName = $user_id . "_" . date("YmdHis") . '.' . $req->mine_dp->extension();
    
        // Check if the technician data exists
        $technician_data = DB::table('members')->where('user_id', $user_id)->first();
    
        if ($technician_data && isset($technician_data->profile_photo)) {
            // Delete the previous file
            $oldFile = public_path($technician_data->profile_photo);
            if (File::exists($oldFile)) {
                File::delete($oldFile);
            }
        }
    
        // Ensure the directory exists
        $directoryPath = public_path('images/dp');
        if (!File::exists($directoryPath)) {
            if (!File::makeDirectory($directoryPath, 0777, true, true)) {
                return response()->json(["success" => false, "message" => "Failed to create directory!"], 500);
            }
        }
    
        // Move the file to the directory
        if (!$req->mine_dp->move($directoryPath, $imageName)) {
            return response()->json(["success" => false, "message" => "File upload failed!"], 500);
        }
    
        // Store file path in database
        $imagePath = "/images/dp/" . $imageName;
        $update = DB::table('members')->where('user_id', $user_id)->update([
            'profile_photo' => $imagePath
        ]);
    
        if (!$update) {
            return response()->json(["success" => false, "message" => "Database update failed!"], 500);
        }
    
        // Response message
        return response()->json(["success" => true, "message" => "Profile picture uploaded successfully!"]);
    }
}
