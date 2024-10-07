<?php

namespace App\Http\Controllers;

use App\Models\chat;
use App\Models\chat_thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class chatController extends Controller
{
    // get member messages
    function getMemberMessages(Request $request){
        $member_id = $request->input("member_id");
        $member_data = DB::select("SELECT * FROM `members` WHERE `user_id` = ?", [$member_id]);
        if(count($member_data) > 0){
            $chats = DB::select("SELECT chat_thread.*, chat.chat_owner, chat.chat_owner_type FROM `chat_thread` 
                                LEFT JOIN chat ON chat.chat_id = chat_thread.chat_id
                                WHERE chat.chat_owner = '".$member_id."'");
            
            // today
            $today = date("Ymd");
            $segmented_chats = [];
            foreach ($chats as $key => $chat) {
                $chat->date_sent = date("Ymd", strtotime($chat->date_created)) == $today ? date("H:iA", strtotime($chat->date_created)) : date("D dS M Y @ H:iA", strtotime($chat->date_created));
                if(isset($segmented_chats[date("Ymd", strtotime($chat->date_created))])){
                    array_push($segmented_chats[date("Ymd", strtotime($chat->date_created))]['chats'], $chat);
                }else{
                    $segmented_chats[date("Ymd", strtotime($chat->date_created))]['date'] = date("D dS M Y", strtotime($chat->date_created));
                    $segmented_chats[date("Ymd", strtotime($chat->date_created))]['chats'] = [];
                    array_push($segmented_chats[date("Ymd", strtotime($chat->date_created))]['chats'], $chat);
                }
            }

            // return statement
            return response()->json(["success" => true, "data" => $segmented_chats, "member_data" => $member_data[0]]);
        }else{
            return response()->json(["success" => false, "message" => "Member is not present!"]);
        }
    }

    function sendMessage(Request $request){
        // input
        $member_id = $request->input("member_id");
        $message = $request->input("message");
        $authentication_code = $request->header('maru-authentication_code');

        // send the message but first get the sender id and type using the token
        $check_authentication_code = DB::select("SELECT * FROM `credentials` WHERE `authentication_code` = ?", [$authentication_code]);
        
        // user id and type
        $user_id = $check_authentication_code[0]->user_id;
        $user_type = $check_authentication_code[0]->user_type;

        // check if the chat of the user is present
        $chat = DB::select("SELECT * FROM `chat` WHERE `chat_owner` = ? ORDER BY `chat_id` DESC", [$member_id]);
        if(count($chat) == 0){
            // record a new chat
            $one_chat = new chat();
            $one_chat->chat_owner = $member_id;
            $one_chat->chat_owner_type = "4";
            $one_chat->chat_status = "active";
            $one_chat->date_created = date("YmdHis");
            $one_chat->save();

            // record chat thread
            $chat_thread = new chat_thread();
            $chat_thread->chat_id = $one_chat->chat_id;
            $chat_thread->message = $message;
            $chat_thread->message_status = "sent";
            $chat_thread->date_created = date("YmdHis");
            $chat_thread->receiver_id = $user_id;
            $chat_thread->receiver_type = $user_type;
            $chat_thread->save();
        }else {
            // record chat thread
            $chat_thread = new chat_thread();
            $chat_thread->chat_id = $chat[0]->chat_id;
            $chat_thread->message = $message;
            $chat_thread->message_status = "sent";
            $chat_thread->date_created = date("YmdHis");
            $chat_thread->receiver_id = $user_id;
            $chat_thread->receiver_type = $user_type;
            $chat_thread->save();
        }

        // success
        return response()->json(["success" => true, "message" => "Message sent successfully!"]);
    }
}
