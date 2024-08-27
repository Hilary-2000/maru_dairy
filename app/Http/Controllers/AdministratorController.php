<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Credential;
use App\Models\Member;
use App\Models\MilkPrice;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdministratorController extends Controller
{
    //GET THE ADMINISTRATOR ADMIN
    function admin_dashboard(Request $request, $period){
        // get the milk collection statistics
        $start = date("Ymd", strtotime(($period*-1)." days"))."000000";
        $end = date("Ymd")."235959";
        $starts = "20200101000000";
        $report_period = date("dS M Y", strtotime($start))." - ".date("dS M Y", strtotime($end));

        // previous
        $previous_start = date("Ymd", strtotime(($period*-2)." days"))."000000";
        $previous_end = date("Ymd", strtotime(($period*-1)." days"))."235959";

        // get the total collection for that period
        $milk_collection = DB::select("SELECT SUM(`collection_amount`) AS 'total' FROM `milk_collections` WHERE collection_date BETWEEN ? AND ?", [$start, $end]);
        $prev_milk_collection = DB::select("SELECT SUM(`collection_amount`) AS 'total' FROM `milk_collections` WHERE collection_date BETWEEN ? AND ?", [$previous_start, $previous_end]);
        $members_present = DB::select("SELECT COUNT(*) AS 'total' FROM `members`");
        $member_registered = DB::select("SELECT COUNT(*) AS 'total' FROM `members` WHERE `date_registered` BETWEEN ? AND ?", [$start, $end]);
        $prev_member_registered = DB::select("SELECT COUNT(*) AS 'total' FROM `members` WHERE `date_registered` BETWEEN ? AND ?", [$previous_start, $previous_end]);

        // calculate if its and increase or a decrease
        $current_milk_collection = $milk_collection[0]->total ?? 0;
        $previous_milk_collection = $prev_milk_collection[0]->total ?? 0;

        $milk_collection_status = "constant";
        $milk_precentage = 0;
        if($current_milk_collection > 0 && $previous_milk_collection > 0){
            if($current_milk_collection > $previous_milk_collection){
                $milk_collection_status = "increase";
                $milk_precentage = (($current_milk_collection - $previous_milk_collection) / $previous_milk_collection) * 100;
            }elseif($previous_milk_collection > $current_milk_collection){
                $milk_collection_status = "decrease";
                $milk_precentage = (($previous_milk_collection - $current_milk_collection) / $previous_milk_collection) * 100;
            }
        }
        $milk_precentage = round($milk_precentage, 2);

        // members
        $current_members = $member_registered[0]->total ?? 0;
        $previous_members = $prev_member_registered[0]->total ?? 0;
        $member_status = "constant";
        $member_percentage = 0;

        if ($current_members > 0 && $previous_members > 0) {
            if ($current_members > $previous_members) {
                $member_status = "increase";
                $member_percentage = (($current_members - $previous_members) / $previous_members) * 100;
            }elseif ($previous_members > $current_members) {
                $member_status = "decrease";
                $member_percentage = (($previous_members - $current_members) / $previous_members) * 100;
            }
        }
        $member_percentage = round($member_percentage, 2);
        
        // period
        $collection_graph_data = [];
        $member_present_graph_data = [];
        $member_registered_graph_data = [];
        if ($period == "7") {
            // select
            $counter = 0;
            for ($index = 0; $index < 7; $index++) {
                $start_date = date("Ymd", strtotime("-".($counter)." days"))."000000";
                $end_date = date("Ymd", strtotime("-".$counter." days"))."235959";

                // collection data
                $collection = DB::select("SELECT SUM(`collection_amount`) AS 'Total' FROM `milk_collections` WHERE collection_date BETWEEN ? AND ?", [$start_date, $end_date]);
                $data = array("date" => date("D dS M Y", strtotime($start_date))." - ".date("D dS M Y", strtotime($end_date)), "collection" => $collection[0]->Total ?? 0, "label" => date("d-m", strtotime($end_date)));
                array_push($collection_graph_data, $data);
                
                // counter
                $member_counter = DB::select("SELECT COUNT(*) AS 'total' FROM `members` WHERE `date_registered` BETWEEN ? AND ?", [$starts, $end_date]);
                $data = array("date" => date("D dS M Y", strtotime($end_date)), "collection" => $member_counter[0]->total ?? 0, "label" => date("d-m", strtotime($end_date)));
                array_push($member_present_graph_data, $data);
                
                // counter
                $member_counter = DB::select("SELECT COUNT(*) AS 'total' FROM `members` WHERE `date_registered` BETWEEN ? AND ?", [$start_date, $end_date]);
                $data = array("date" => date("D dS M Y", strtotime($end_date)), "collection" => $member_counter[0]->total ?? 0, "label" => date("d-m", strtotime($end_date)));
                array_push($member_registered_graph_data, $data);
                $counter+=1;
            }
        }elseif ($period == "14") {
            // select
            $counter = 0;
            for ($index = 0; $index < 7; $index++) {
                $start_date = date("Ymd", strtotime("-".($counter+2)." days"))."000000";
                $end_date = date("Ymd", strtotime("-".$counter." days"))."235959";

                // collection data
                $collection = DB::select("SELECT SUM(`collection_amount`) AS 'Total' FROM `milk_collections` WHERE collection_date BETWEEN ? AND ?", [$start_date, $end_date]);
                $data = array("date" => date("D dS M Y", strtotime($start_date))." - ".date("D dS M Y", strtotime($end_date)), "collection" => $collection[0]->Total ?? 0, "label" => date("d-m", strtotime($end_date)));
                array_push($collection_graph_data, $data);
                
                // counter
                $member_counter = DB::select("SELECT COUNT(*) AS 'total' FROM `members` WHERE `date_registered` BETWEEN ? AND ?", [$starts, $end_date]);
                $data = array("date" => date("D dS M Y", strtotime($end_date)), "collection" => $member_counter[0]->total ?? 0, "label" => date("d-m", strtotime($end_date)));
                array_push($member_present_graph_data, $data);
                
                // counter
                $member_counter = DB::select("SELECT COUNT(*) AS 'total' FROM `members` WHERE `date_registered` BETWEEN ? AND ?", [$start_date, $end_date]);
                $data = array("date" => date("D dS M Y", strtotime($end_date)), "collection" => $member_counter[0]->total ?? 0, "label" => date("d-m", strtotime($end_date)));
                array_push($member_registered_graph_data, $data);
                
                // counter
                $counter+=2;
            }
        }elseif ($period == "30") {
            // select
            $counter = 0;
            for ($index = 0; $index < 7; $index++) {
                $start_date = date("Ymd", strtotime("-".($counter+4)." days"))."000000";
                $end_date = date("Ymd", strtotime("-".$counter." days"))."235959";

                // collection data
                $collection = DB::select("SELECT SUM(`collection_amount`) AS 'Total' FROM `milk_collections` WHERE collection_date BETWEEN ? AND ?", [$start_date, $end_date]);
                $data = array("date" => date("D dS M Y", strtotime($start_date))." - ".date("D dS M Y", strtotime($end_date)), "collection" => $collection[0]->Total ?? 0, "label" => date("d-m", strtotime($end_date)));
                array_push($collection_graph_data, $data);
                
                // counter
                $member_counter = DB::select("SELECT COUNT(*) AS 'total' FROM `members` WHERE `date_registered` BETWEEN ? AND ?", [$starts, $end_date]);
                $data = array("date" => date("D dS M Y", strtotime($end_date)), "collection" => $member_counter[0]->total ?? 0, "label" => date("d-m", strtotime($end_date)));
                array_push($member_present_graph_data, $data);
                
                // counter
                $member_counter = DB::select("SELECT COUNT(*) AS 'total' FROM `members` WHERE `date_registered` BETWEEN ? AND ?", [$start_date, $end_date]);
                $data = array("date" => date("D dS M Y", strtotime($end_date)), "collection" => $member_counter[0]->total ?? 0, "label" => date("d-m", strtotime($end_date)));
                array_push($member_registered_graph_data, $data);
                
                // counter
                $counter+=4;
            }
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

        // get the admin data
        $authentication_code = $request->header("maru-authentication_code");
        $credential = Credential::where("authentication_code", $authentication_code)->first();
        $member_data = Administrator::find($credential->user_id);

        // milk_collection
        return response()->json(["success" => true, "report_period" => $report_period, "member_data" => $member_data, "time_of_day" => $time_of_day, "collection_status" => $milk_collection_status, "collection_percentage" => $milk_precentage, "total_collection" => $milk_collection[0]->total ?? 0, "collection_graph_data" => $collection_graph_data, "members_present" => $members_present[0]->total ?? 0, "member_present_graph" => $member_present_graph_data, "members_registered" => $member_registered[0]->total ?? 0,  "member_status" => $member_status, "member_percentage" => $member_percentage, "member_registered_graph" => $member_registered_graph_data]);

    }

    // MEMBERS
    function admin_members(){
        $members = DB::select("SELECT * FROM `members` ORDER BY `user_id` DESC");
        return response()->json(["success" => true, "members"=>$members]);
    }
    
    // view profile
    function member_details($member_id){
        $member_details = Member::find($member_id);
        if ($member_details) {
            $collection_days = DB::select("SELECT COUNT(*) AS 'total' FROM `milk_collections` WHERE `member_id` = ?",[$member_details->user_id]);
            $total_collection = DB::select("SELECT SUM(`collection_amount`) AS 'total' FROM `milk_collections` WHERE `member_id` = ?", [$member_details->user_id]);
            
            // return value
            return response()->json(["success" => true, "collection_days" => number_format(count($collection_days) > 0 ? $collection_days[0]->total ?? 0 : 0), "total_collection" => number_format(count($total_collection) > 0 ? $total_collection[0]->total ?? 0 : 0), "member_details" => $member_details]);
        }else{
            return response()->json(["success" => false, "message" => "Member has been not been found!"]);
        }
    }

    function updateMember(Request $request){
        $fullname = $request->input("fullname");
        $gender = $request->input("gender");
        $phone_number = $request->input("phone_number");
        $email = $request->input("email");
        $animals = $request->input("animals");
        $residence = $request->input("residence");
        $region = $request->input("region");
        $membership = $request->input("membership");
        $national_id = $request->input("national_id");
        $user_id = $request->input("user_id");

        // update the member
        $member = Member::find($user_id);
        if ($member) {
            // check for national id except him
            $find_natid = DB::select("SELECT * FROM `members` WHERE `national_id` = ? AND `user_id` != ?",[$national_id, $user_id]);
            if (count($find_natid) > 0) {
                return response()->json(["success" => false, "message" => "The national id provided has been used!"]);
            }

            // check for national id except him
            $find_phone = DB::select("SELECT * FROM `members` WHERE `phone_number` = ? AND `user_id` != ?",[$phone_number, $user_id]);
            if (count($find_phone) > 0) {
                return response()->json(["success" => false, "message" => "The phone number provided has been used!"]);
            }

            // check for national id except him
            $find_membership = DB::select("SELECT * FROM `members` WHERE `membership` = ? AND `user_id` != ?",[$membership, $user_id]);
            if (count($find_membership) > 0) {
                return response()->json(["success" => false, "message" => "The membership number provided has been used!"]);
            }

            $member->fullname = $fullname;
            $member->gender = $gender;
            $member->phone_number = $phone_number;
            $member->email = $email;
            $member->animals = $animals;
            $member->residence = $residence;
            $member->region = $region;
            $member->membership = $membership;
            $member->national_id = $national_id;
            $member->save();

            // return
            return response()->json(["success" => true, "message" => "Member has been updated successfully!"]);
        }else{
            return response()->json(["success" => false, "message" => "Update has failed, try again later!"]);
        }
    }

    function getMemberHistory($member_id){
        
        // get the member data
        $member = Member::find($member_id);
        
        // get the member history
        if($member){
            // member
            $collection_history = DB::select("SELECT * FROM `milk_collections` WHERE `member_id` = ? AND `collection_date` ORDER BY `collection_id` DESC", [$member->user_id]);
            $count = DB::select("SELECT SUM(collection_amount) AS 'total' FROM `milk_collections` WHERE `member_id` = ?", [$member->user_id]);
            
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

    function addNewMember(Request $request){
        // phone number
        $phone_number = $request->input("phone_number");
        $national_id = $request->input("national_id");
        $membership = $request->input("membership");

        // check for national id except him
        $find_natid = DB::select("SELECT * FROM `members` WHERE `national_id` = ?",[$national_id]);
        if (count($find_natid) > 0) {
            return response()->json(["success" => false, "message" => "The national id provided has been used!"]);
        }

        // check for national id except him
        $find_phone = DB::select("SELECT * FROM `members` WHERE `phone_number` = ?",[$phone_number]);
        if (count($find_phone) > 0) {
            return response()->json(["success" => false, "message" => "The phone number provided has been used!"]);
        }

        // check for national id except him
        $find_membership = DB::select("SELECT * FROM `members` WHERE `membership` = ?",[$membership]);
        if (count($find_membership) > 0) {
            return response()->json(["success" => false, "message" => "The membership number provided has been used!"]);
        }


        // insert a new member
        $member = new Member();
        $member->fullname = $request->input("fullname");
        $member->phone_number = $request->input("phone_number");
        $member->email = $request->input("email");
        $member->residence = $request->input("residence");
        $member->region = $request->input("region");
        $member->national_id = $request->input("national_id");
        $member->animals = $request->input("animals");
        $member->membership = $request->input("membership");
        $member->gender = $request->input("gender");
        $member->username = $member->phone_number;
        $member->date_registered = date("YmdHis");
        $member->password = "1234";
        $member->save();

        $credential = new Credential();
        $credential->user_id = $member->user_id;
        $credential->username = $member->phone_number;
        $credential->password = "1234";
        $credential->user_type = "4";
        $credential->save();

        return response()->json(["success" => true, "message" => "User added successfully!"]);
    }

    function viewProfile(Request $request){
        // authentication_code
        $authentication_code = $request->header("maru-authentication_code");
        
        // credential
        $credential = Credential::where("authentication_code", $authentication_code)->first();
        if($credential){
            // find user
            $administrator = Administrator::find($credential->user_id);
            if ($administrator) {
                return response()->json(["success" => true, "administrator" => $administrator]);
            }else{
                return response()->json(["success" => false, "message" => "Your data can`t be found!"]);
            }
        }else{
            return response()->json(["success" => false, "message" => "Your data can`t be found!"]);
        }
    }

    // update profile
    function updateProfile(Request $request){
        // authentication_code
        $authentication_code = $request->header("maru-authentication_code");
        // credential
        $credential = Credential::where("authentication_code", $authentication_code)->first();
        if($credential){
            // find user
            $administrator = Administrator::find($credential->user_id);
            if ($administrator) {
                $administrator->fullname = $request->input("fullname");
                $administrator->phone_number = $request->input("phone_number");
                $administrator->email = $request->input("email");
                $administrator->residence = $request->input("residence");
                $administrator->region = $request->input("region");
                $administrator->save();

                // return response
                return response()->json(["success" => true, "message" => "Update has been done successfully!"]);
            }else{
                return response()->json(["success" => false, "message" => "Your data can`t be found!"]);
            }
        }else{
            return response()->json(["success" => false, "message" => "Your data can`t be found!"]);
        }
    }

    function getMilkPrices(){
        $milk_prices = DB::select("SELECT * FROM `milk_prices` ORDER BY `effect_date` DESC");
        for ($index=count($milk_prices) - 1; $index >= 0; $index--) {
            $milk_prices[$index]->end_date = $index > 0 ? $this->modifyDate($milk_prices[$index-1]->effect_date, 1, "subtract") : date("YmdHis");
            $milk_prices[$index]->effect_date = date("dS M Y", strtotime($milk_prices[$index]->effect_date));
            $milk_prices[$index]->end_date = date("dS M Y", strtotime($milk_prices[$index]->end_date));
            $milk_prices[$index]->amount = (round($milk_prices[$index]->amount, 2));
        }

        // current_price
        $current_price = DB::select("SELECT * FROM `milk_prices` ORDER BY `effect_date` DESC");
        return response()->json(["success" => true, "milk_prices" => $milk_prices, "current_price" => (round((count($current_price) > 0 ? $current_price[0]->amount : 0), 2))]);
    }

    // function to reduce the day
    function modifyDate($date, $days, $operation = 'add') {
        // Create a DateTime object from the given date
        $dateTime = new DateTime($date);
        
        // Determine whether to add or subtract days
        if ($operation === 'add') {
            $dateTime->modify("+{$days} days");
        } elseif ($operation === 'subtract') {
            $dateTime->modify("-{$days} days");
        }
        
        // Return the modified date in the format Y-m-d
        return $dateTime->format('YmdHis');
    }

    function insertDate(Request $request){
        $amount = $request->input("amount");
        $effect_date = $request->input("effect_date");
        $status = $request->input("status");
        
        // insert the data
        $milk_price = new MilkPrice();
        $milk_price->amount = $amount;
        $milk_price->status = $status;
        $milk_price->effect_date = date("YmdHis", strtotime($effect_date));
        $milk_price->save();
        
        // insert data
        return response()->json(["success" => true, "message" => "Data has been inserted successfully!"]);
    }

    // get milk details
    function getMilkDetails($milk_id){
        $milk_price_details = MilkPrice::find($milk_id);
        if($milk_price_details){
            $milk_price_details->effect_date = date("Y-m-d H:i:s", strtotime($milk_price_details->effect_date));

            // current_price
            $current_price = DB::select("SELECT * FROM `milk_prices` ORDER BY `effect_date` DESC");
            return response()->json(["success" => true, "milk_price_details" => $milk_price_details, "message" => "Milk details found!", "current_price" => (round((count($current_price) > 0 ? $current_price[0]->amount : 0), 2))]);
        }else{
            return response()->json(["success" => false, "message" => "Milk details can`t be found!"]);
        }
    }

    function updateMilk(Request $request){
        // update the milk details
        $milk_price_details = MilkPrice::find($request->input("price_id"));
        if ($milk_price_details) {
            // update the milk details
            $milk_price_details->amount = $request->input("amount");
            $milk_price_details->status = $request->input("status");
            $milk_price_details->effect_date = date("YmdHis", strtotime($request->input("effect_date")));
            $milk_price_details->save();
            return response()->json(['success' => true, "message" => "Update has been done successfully!"]);
        }else{
            return response()->json(['success' => false, "message" => "Invalid milk details, Maybe it`s deleted!"]);
        }
    }
}
