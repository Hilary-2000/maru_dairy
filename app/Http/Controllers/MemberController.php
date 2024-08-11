<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Models\Member;
use Illuminate\Http\Request;

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
        return response()->json(["success" => true, "data" => $members]);
    }
}
