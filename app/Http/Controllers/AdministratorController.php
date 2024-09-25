<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Credential;
use App\Models\Member;
use App\Models\MilkPrice;
use App\Models\SuperAdministrator;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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
        $member_data = $credential->user_type == "2" ? Administrator::find($credential->user_id) : SuperAdministrator::find($credential->user_id);

        // milk_collection
        return response()->json(["success" => true, "report_period" => $report_period, "member_data" => $member_data, "time_of_day" => $time_of_day, "collection_status" => $milk_collection_status, "collection_percentage" => $milk_precentage, "total_collection" => $milk_collection[0]->total ?? 0, "collection_graph_data" => $collection_graph_data, "members_present" => $members_present[0]->total ?? 0, "member_present_graph" => $member_present_graph_data, "members_registered" => $member_registered[0]->total ?? 0,  "member_status" => $member_status, "member_percentage" => $member_percentage, "member_registered_graph" => $member_registered_graph_data]);

    }

    // MEMBERS
    function admin_members(){
        $members = DB::select("SELECT * FROM `members` ORDER BY `fullname` ASC");
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
            
            // collection price
            // collection history iteration
            foreach ($collection_history as $key => $value) {
                $collection_history[$key]->date = date("D dS M Y", strtotime($value->collection_date));
                $collection_history[$key]->time = date("h:iA", strtotime($value->collection_date));
            }
            $total_price = $this->getTotalPrice($collection_history);

            // collection history
            return response()->json(["success" => true, "total_price" => number_format($total_price, 2), "count" => number_format((count($count) > 0 ? ($count[0]->total ?? 0) : 0), 2), "collection_history" => $collection_history]);
        }else{
            // collection history
            return response()->json(["success" => false, "message" => "An error has occured!"]);
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

    function getTotalLitres($data){
        $litres_collected = 0;
        foreach ($data as $key => $value) {
            // collection price
            $collection_amount = $value->collection_amount;
            $litres_collected += $collection_amount;
        }

        // return price
        return $litres_collected;
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
            $administrator = $credential->user_type == "2" ? Administrator::find($credential->user_id) : SuperAdministrator::find($credential->user_id);
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
            $username = DB::select("SELECT * FROM `credentials` WHERE `username` = '".$request->input("username")."' AND `user_id` = '".$credential->user_id."'");
            if(count($username) > 0){
                return response()->json(["success" => false, "message" => "Username is already taken!"]);
            }

            // administrator
            $administrator = $credential->user_type == "2" ? Administrator::find($credential->user_id) : SuperAdministrator::find($credential->user_id);
            if ($administrator) {
                $administrator->fullname = $request->input("fullname");
                $administrator->phone_number = $request->input("phone_number");
                $administrator->email = $request->input("email");
                $administrator->residence = $request->input("residence");
                $administrator->region = $request->input("region");
                $administrator->username = $request->input("username");
                $administrator->save();

                $credential->username = $request->input("username");
                $credential->save();

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
        $milk_prices = DB::select("SELECT * FROM `milk_prices` ORDER BY `price_id` DESC");
        $found = false;
        $current_price = 0;
        for ($index = count($milk_prices) - 1; $index >= 0; $index--) {
            $milk_prices[$index]->end_date = $index > 0 ? $this->modifyDate($milk_prices[$index-1]->effect_date, 1, "subtract") : date("YmdHis");
            $milk_prices[$index]->effect_date = date("dS M Y", strtotime($milk_prices[$index]->effect_date));
            $milk_prices[$index]->end_date = date("dS M Y", strtotime($milk_prices[$index]->end_date));
            $milk_prices[$index]->amount = (round($milk_prices[$index]->amount, 2));
        }

        for ($index = 0; $index < count($milk_prices); $index++) {
            // current price
            $milk_prices[$index]->current = $milk_prices[$index]->status == 1 && !$found;
            $milk_prices[$index]->effect_date = date("Ymd", strtotime($milk_prices[$index]->effect_date)) > date("Ymd", strtotime($milk_prices[$index]->end_date)) ? "inactive" : $milk_prices[$index]->effect_date;
            $milk_prices[$index]->end_date =  $milk_prices[$index]->effect_date == "inactive" ? "inactive" : $milk_prices[$index]->end_date;
            
            // check milk prices
            if ($milk_prices[$index]->status == 1 && !$found) {
                $found = true;
                $current_price = $milk_prices[$index]->amount;
            }
        }
        
        // last_date
        $last_date = count($milk_prices) > 0 ? date("Y-m-d", strtotime($this->modifyDate($milk_prices[0]->effect_date, 1, "add"))) : date("Y-m-d", strtotime("-5 years"));

        // return value
        return response()->json(["success" => true, "milk_prices" => $milk_prices, "current_price" => $current_price, "last_date" => $last_date]);
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
            $current_price = DB::select("SELECT * FROM `milk_prices` ORDER BY `price_id` DESC");
            $last_date = date("Y-m-d", strtotime("-5 years"));
            if (count($current_price) > 0) {
                foreach ($current_price as $key => $value) {
                    if(count($current_price) > 1){
                        if ($key+1 == $milk_id) {
                            $last_date = date("Y-m-d", strtotime($this->modifyDate($value->effect_date, 1)));
                            break;
                        }
                    }else{
                        $last_date = date("Y-m-d", strtotime("-5 years"));
                        break;
                    }
                }
            }
            return response()->json(["success" => true, "minimum_date" => $last_date, "milk_price_details" => $milk_price_details, "message" => "Milk details found!", "current_price" => (round((count($current_price) > 0 ? $current_price[0]->amount : 0), 2))]);
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

    // milk price
    function milkPrice(){
        $milk_price = DB::select("SELECT * FROM `milk_prices` WHERE `status` = '1' ORDER BY `price_id` DESC LIMIT 1");
        $price = count($milk_price) > 0 ? $milk_price[0]->amount : 0;
        return response()->json(["success" => true, "price" => $price]);
    }

    function displayAdministrators(){
        $administrators = DB::select("SELECT * FROM `administrators` ORDER BY `user_id` DESC");
        return response()->json(["success" => true, "administrators" => $administrators]);
    }

    function adminDetails($administrator_id){
        $administrator = DB::select("SELECT * FROM `administrators` WHERE `user_id` = ? ORDER BY `user_id` DESC", [$administrator_id]);
        if (count($administrator)) {
            // return response
            return response()->json(["success" => true, "administrator" => $administrator[0]]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid administrator!"]);
        }
    }

    function deleteAdmin($administrator_id){
        $administrator = DB::select("SELECT * FROM `administrators` WHERE `user_id` = ? ORDER BY `user_id` DESC", [$administrator_id]);
        if (count($administrator)) {
            // return response
            $delete = DB::delete("DELETE FROM `administrators` WHERE `user_id` = ? ", [$administrator_id]);
            return response()->json(["success" => true, "message" => "Administrator has been deleted successfully!"]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid administrator!"]);
        }
    }

    function updateAdministrator(Request $request){
        // check phone number and id
        $check_phone = DB::select("SELECT * FROM `administrators` WHERE `phone_number` = ? AND `user_id` != ?", [$request->input("phone_number"), $request->input("user_id")]);
        if (count($check_phone) > 0) {
            return response()->json(["success" => false, "message" => "Phone number has been used!"]);
        }

        // check id
        $check_id = DB::select("SELECT * FROM `administrators` WHERE `national_id` = ? AND `user_id` != ?", [$request->input("national_id"), $request->input("user_id")]);
        if (count($check_id) > 0) {
            return response()->json(["success" => false, "message" => "National id number has been used!"]);
        }

        // update technician
        $technician = Administrator::find($request->input("user_id"));
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

            return response()->json(["success" => true, "message" => "Administrator has been updated successfully!"]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid administrator!"]);
        }
    }

    function registerAdministrator(Request $request){
        // check phone number and id
        $check_phone = DB::select("SELECT * FROM `administrators` WHERE `phone_number` = ? AND `user_id` != ?", [$request->input("phone_number"), $request->input("user_id")]);
        if (count($check_phone) > 0) {
            return response()->json(["success" => false, "message" => "Phone number has been used!"]);
        }

        // check id
        $check_id = DB::select("SELECT * FROM `administrators` WHERE `national_id` = ? AND `user_id` != ?", [$request->input("national_id"), $request->input("user_id")]);
        if (count($check_id) > 0) {
            return response()->json(["success" => false, "message" => "National id number has been used!"]);
        }

        // check username in credentials
        $check_username = DB::select("SELECT * FROM `credentials` WHERE `username` = ?", [$request->input("username")]);
        if (count($check_username) > 0) {
            return response()->json(["success" => false, "message" => "Username has been taken!"]);
        }

        $administrator = new Administrator();
        $administrator->fullname = $request->input("fullname");
        $administrator->phone_number = $request->input("phone_number");
        $administrator->email = $request->input("email");
        $administrator->residence = $request->input("residence");
        $administrator->region = $request->input("region");
        $administrator->national_id = $request->input("national_id");
        $administrator->gender = $request->input("gender");
        $administrator->status = $request->input("status");
        $administrator->username = $request->input("username");
        $administrator->password = $request->input("password");
        $administrator->profile_photo = "";
        $administrator->save();
        
        // register their credentials
        $credential = new Credential();
        $credential->user_id = $administrator->user_id;
        $credential->username = $request->input("username");
        $credential->password = $request->input("password");
        $credential->user_type = "2";
        $credential->save();

        return response()->json(["success" => true, "message" => "Administrator has been added successfully!"]);
    }

    function upload_dp(Request $req)
    {
        // authentication_code
        $authentication_code = $req->header("maru-authentication_code");
        
        // credential
        $credential = Credential::where("authentication_code", $authentication_code)->first();

        if ($credential) {
            $user_type = $credential->user_type;
            // Validate the request
            $req->validate([
                'mine_dp' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
                'user_id' => 'required|integer'
            ]);

            // table_type
            $table_type = $user_type == "2" ? "administrators" : "super_administrators";
        
            // Set variables
            $user_id = $req->input('user_id');
            $imageName = $user_id . "_" . date("YmdHis") . '.' . $req->mine_dp->extension();
        
            // Check if the technician data exists
            $technician_data = DB::table($table_type)->where('user_id', $user_id)->first();
        
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
            $update = DB::table($table_type)->where('user_id', $user_id)->update([
                'profile_photo' => $imagePath
            ]);
        
            if (!$update) {
                return response()->json(["success" => false, "message" => "Database update failed!"], 500);
            }
        
            // Response message
            return response()->json(["success" => true, "message" => "Profile picture uploaded successfully!"]);
        }else{
            // Response message
            return response()->json(["success" => false, "message" => "An error has occured!"]);
        }
    }
}
