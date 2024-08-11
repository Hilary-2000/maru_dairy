<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


date_default_timezone_set('Africa/Nairobi');

class Credentials extends Controller
{
    //process login
    public function process_login(Request $request){
        $validate = Validator::make($request->all(), [
            "username" => "required",
            "password" => "required"
        ]);

        if ($validate->fails()) {
            $data = [
                "success" => false,
                "message" => $validate->messages()
            ];

            // json_data
            return response()->json($data,422);
        }

        $username = $request->input("username");
        $password = $request->input("password");

        // get the username and password
        $credentials = Credential::where("username", $username)->where("password", $password)->first();
        // return $credentials;

        // set the token and return it to the user
        if (isset($credentials)) {
            // set the token and login
            $token = $this->getToken();

            // update this on the database
            $credential = Credential::find($credentials->credential_id);
            $credential->last_login_date = date("YmdHis");
            $credential->authentication_code = $token;
            $credential->save();

            // get the user data
            if($credentials->user_type == "1"){
                
            }elseif($credentials->user_type == "2"){
                
            }elseif($credentials->user_type == "3"){
                
            }else{
                
            }

            // return the response
            return response()->json(["success" => true, "message" => "Correct Credential!", "token" => $token, "user_type" => $credential->user_type], 200);
        }else{
            // return an error to the user
            return response()->json(["success" => false, "message" => "Invalid credential, Try again!"], 401);
        }
    }

    public function getToken($character = 16){
        $uppercaseLetters = range('A', 'Z');
        $lowercaseLetters = range('a', 'z');
        $numbers = range(0,9);

        $token = "";
        for ($i=0; $i < $character; $i++) { 
            $select = rand(1,3);

            if ($select == 1) {
                $random = rand(0,25);
                $token .= $uppercaseLetters[$random]."";
            }elseif ($select == 2) {
                $random = rand(0,25);
                $token .= $lowercaseLetters[$random]."";
            }elseif ($select == 3) {
                $random = rand(0,8);
                $token .= $numbers[$random]."";
            }
        }
        return $token;
    }

    function checkToken(Request $request){
        if($request->input("token") !== null){
            $credential = Credential::where("authentication_code", $request->input("token"))->first();
            if ($credential !== null) {
                // are valid credentials
                return response()->json(["success" => true, "data" => $credential], 200);
            }else {
                return response()->json(["success" => false, "message" => "Invalid token"], 401);
            }
        }else {
            return response()->json(["success" => false, "message" => "Invalid token"], 401);
        }
    }
}
