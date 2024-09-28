<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Classes\phpmailer\src\PHPMailer;


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

    function resetPassword(Request $request){
        // reset password
        $username = $request->input("username");

        // scheck if the username is valif
        $credential = DB::select("SELECT * FROM `credentials` WHERE `username` = ?", [$username]);

        if (count($credential) > 0) {
            // reset password
            $new_password = $this->getToken(8);
            $update = DB::update("UPDATE `credentials` SET `password` = ? WHERE `username` = ?", [$new_password, $username]);

            // send the passowrd via the email
            $select = "";
            $table_name = "";
            if($credential[0]->user_type == "1"){
                $select = "SELECT * FROM `technicians` WHERE `user_id` = ?";
                $table_name = "technicians";
            }elseif($credential[0]->user_type == "2"){
                $select = "SELECT * FROM `administrators` WHERE `user_id` = ?";
                $table_name = "administrators";
            }elseif($credential[0]->user_type == "3"){
                $select = "SELECT * FROM `super_administrators` WHERE `user_id` = ?";
                $table_name = "super_administrators";
            }elseif($credential[0]->user_type == "4"){
                $select = "SELECT * FROM `members` WHERE `user_id` = ?";
                $table_name = "members";
            }

            $user_data = DB::select($select, [$credential[0]->user_id]);

            // update the users data
            $update = DB::update("UPDATE `$table_name` SET `password` = ? WHERE `user_id` = ?", [$new_password, $credential[0]->user_id]);

            // send the email to that email address.
            if(count($user_data) > 0){
                $email = $user_data[0]->email;
                if ($email != null && strlen($email) > 0) {
                    // USE PHP MAILER
                    $sender_name = "Maru Dairy Co-op";
                    $email_username = "hilaryme45@gmail.com";
                    $email_password = "qdwjzlufnyxfncrb";
                    
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $email_username;
                    $mail->Password = $email_password;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
    
                    $message = "<div style='font-family:nunito;'>Hello ".ucwords(strtolower($user_data[0]->fullname)).",\r\n\r\n<br>";  // Double new line for spacing between paragraphs
                    $message .= "I hope this email finds you well.\r\n\r\n<br>";  // More content
                    $message .= "Your password has been reset successfully. Use it to login and set your new password!\r\n\r\n<br><br>";  // Another new line
                    $message .= "Your password is : <strong>".$new_password."</strong>\r\n\r\n<br><br>";  // Another new line
                    $message .= "<p style='font-color:red;'><strong>If this password reset was not initiated by you we highly recommend you use this password to reset both your username and password!</strong>\r\n\r\n<br><br>";  // Another new line
                    $message .= "Regards,\r\n<br>";  // Signature block
                    $message .= "Maru Diary Cooperative Ltd</div>";

                    $message = "<html>
                                <head>
                                <title>Formatted Email Example</title>
                                <style>
                                    @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap');
                                </style>
                                </head>
                                <body style='font-family: Nunito, sans-serif; font-size: 15px;'>
                                    <p>Hello ".ucwords(strtolower($user_data[0]->fullname)).",</p>
                                    <p>Your password has been reset successfully. Use it to login and set your new password!</p>
                                    <p>Your new password is : <strong>".$new_password."</strong></p>
                                    <p style='color:red;'><b>Note:</b></p>
                                    <p><strong>If this password reset was not initiated by you we highly recommend you use this password to reset both your username and password!</strong></p>
                                    <p>Best regards,<br>Maru Dairy Co-op</p>
                                </body>
                                </html>";
                    
                    
                    $mail->setFrom($email_username,$sender_name);
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = "Maru Dairy Password Reset";
                    $mail->Body = $message;
            
                    $mail->send();
                    $email_explode = explode("@", $email);
                    return response()->json(["success" => true, "message" => "New password has been successfully sent to ".substr($email_explode[0], 0, number_format(strlen($email_explode[0])/2))."*****".$email_explode[1]."!"], 200);
                }else{
                    return response()->json(["success" => false, "message" => "Your email address has not been setup yet!"], 200);
                }
            }
            return response()->json(["success" => false, "message" => "Invalid User!"], 200);
        }
        return response()->json(["success" => false, "message" => "Invalid Credentials"], 200);
    }
}
