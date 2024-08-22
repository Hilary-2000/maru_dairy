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
}
