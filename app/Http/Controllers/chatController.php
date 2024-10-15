<?php

namespace App\Http\Controllers;

use App\Models\chat;
use App\Models\chat_thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;

class chatController extends Controller
{
    // get member messages
    function getMemberMessages(Request $request){
        $member_id = $request->input("member_id");
        $send_status = $request->input("send_status");
        $member_data = DB::select("SELECT * FROM `members` WHERE `user_id` = ?", [$member_id]);
        if(count($member_data) > 0){
            $chats = DB::select("SELECT 
                                CASE
                                    WHEN chat_thread.receiver_type = '4' THEN M.fullname
                                    WHEN chat_thread.receiver_type = '3' THEN SA.fullname
                                    WHEN chat_thread.receiver_type = '2' THEN A.fullname
                                ELSE T.fullname
                                END AS fullname,
                                chat_thread.*, chat.chat_owner, chat.chat_owner_type FROM `chat_thread`
                                LEFT JOIN chat ON chat.chat_id = chat_thread.chat_id
                                LEFT JOIN members AS M ON M.user_id = chat_thread.receiver_id
                                LEFT JOIN administrators AS A ON A.user_id = chat_thread.receiver_id
                                LEFT JOIN super_administrators AS SA ON SA.user_id = chat_thread.receiver_id
                                LEFT JOIN technicians AS T ON T.user_id = chat_thread.receiver_id
                                WHERE chat.chat_owner = '".$member_id."'");
            
            // update the seen status
            $update = DB::select("UPDATE chat_thread SET seen_status = 'seen' WHERE (SELECT chat_owner FROM chat WHERE chat.chat_id = chat_thread.chat_id) = '".$member_id."' AND message_status = '$send_status'");
            
            // segmented chats
            $segmented_chats = [];
            foreach ($chats as $key => $chat) {
                $chat->date_sent = date("H:iA", strtotime($chat->date_created));
                $chat->selected = false;
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
        $message_status = $request->input("message_status");
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
            $chat_thread->message_status = $message_status;
            $chat_thread->date_created = date("YmdHis");
            $chat_thread->receiver_id = $user_id;
            $chat_thread->receiver_type = $user_type;
            $chat_thread->save();
        }else {
            // record chat thread
            $chat_thread = new chat_thread();
            $chat_thread->chat_id = $chat[0]->chat_id;
            $chat_thread->message = $message;
            $chat_thread->message_status = $message_status;
            $chat_thread->date_created = date("YmdHis");
            $chat_thread->receiver_id = $user_id;
            $chat_thread->receiver_type = $user_type;
            $chat_thread->save();
        }

        // success
        return response()->json(["success" => true, "message" => "Message sent successfully!"]);
    }

    function getChats(){
        $chats = DB::select("SELECT chat.*,
                            CASE 
                                WHEN chat.chat_owner_type = '4' THEN M.fullname
                                WHEN chat.chat_owner_type = '3' THEN SA.fullname
                                WHEN chat.chat_owner_type = '2' THEN A.fullname
                                ELSE T.fullname
                            END AS fullname,
                            CASE 
                                WHEN latest_thread.receiver_type = '4' THEN MM.fullname
                                WHEN latest_thread.receiver_type = '3' THEN SSA.fullname
                                WHEN latest_thread.receiver_type = '2' THEN AA.fullname
                                ELSE TT.fullname
                            END AS sender_name,
                            latest_thread.message AS last_message,
                            latest_thread.date_created AS chat_sent,
                            latest_thread.message_status,
                            latest_thread.receiver_type,
                            latest_thread.receiver_id,
                            latest_thread.seen_status
                            FROM `chat`
                            LEFT JOIN (
                                SELECT ct.*
                                FROM chat_thread ct
                                INNER JOIN (
                                    SELECT chat_id, MAX(chat_thread_id) AS max_id
                                    FROM chat_thread
                                    GROUP BY chat_id
                                ) AS latest ON ct.chat_id = latest.chat_id AND ct.chat_thread_id = latest.max_id
                            ) AS latest_thread ON latest_thread.chat_id = chat.chat_id
                            LEFT JOIN members AS M ON chat.chat_owner = M.user_id
                            LEFT JOIN administrators AS A ON chat.chat_owner = A.user_id
                            LEFT JOIN super_administrators AS SA ON chat.chat_owner = SA.user_id
                            LEFT JOIN technicians AS T ON chat.chat_owner = T.user_id
                            LEFT JOIN members AS MM ON latest_thread.receiver_id = MM.user_id
                            LEFT JOIN administrators AS AA ON latest_thread.receiver_id = AA.user_id
                            LEFT JOIN super_administrators AS SSA ON latest_thread.receiver_id = SSA.user_id
                            LEFT JOIN technicians AS TT ON latest_thread.receiver_id = TT.user_id
                            WHERE chat_status = 'active' AND (SELECT `message` FROM `chat_thread` WHERE `chat_id` = chat.chat_id ORDER BY chat_thread_id DESC LIMIT 1) != 'NULL'
                            ORDER BY chat_sent DESC;");
        foreach($chats as $key => $chat){
            $difference = $this->getDateDifference($chat->chat_sent, date("YmdHis"));
            $chats[$key]->chat_sent = $difference['minutes'] < 60 ? number_format($difference['minutes'])."mins ago" : ($difference['days'] < 1 ? date("h:iA", strtotime($chat->chat_sent)) : ($difference['days'] < 7 ? date("D", strtotime($chat->chat_sent)) : date("dS-M", strtotime($chat->chat_sent))));
            $chats[$key]->selected = false;
        }

        // success
        return response()->json(["success" => true, "chats" => $chats]);
    }
    function getDateDifference($date1, $date2) {
        // Create DateTime objects from the date strings
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        
        // Calculate the difference
        $interval = $datetime1->diff($datetime2);
    
        // Get the difference in days, weeks, and months
        $days = $interval->days; // Total days
        $months = $interval->m + ($interval->y * 12); // Total months
        $weeks = floor($days / 7); // Approximate weeks
        $totalMinutes = abs(strtotime($date2) - strtotime($date1)) / 60;
    
        // Format the result
        return [
            'days' => $days,
            'weeks' => $weeks,
            'months' => $months,
            'minutes' => $totalMinutes
        ];
    }

    function deleteChatThreads(Request $request){
        // chat thread ids
        $chat_thread_ids = $request->input("chat_thread_ids");
        $chat_thread = join(",", $chat_thread_ids);

        $delete_threads = DB::delete("DELETE FROM `chat_thread` WHERE `chat_thread_id` IN ($chat_thread)");

        return response()->json(["success" => true, "message" => "Chat thread(s) deleted successfully!"]);
    }

    function deleteChats(Request $request){
        // chat thread ids
        $chat_ids = $request->input("chat_ids");
        $chat_id = join(",", $chat_ids);
        
        $delete_chat = DB::delete("DELETE FROM chat WHERE chat_id IN ($chat_id)");
        $delete_threads = DB::delete("DELETE FROM chat_thread WHERE chat_id IN ($chat_id)");

        return response()->json(["success" => true, "message" => "Chats deleted successfully!"]);
    }

    function notificationCount(Request $request){
        $chats = DB::select("SELECT chat.*,
                            CASE 
                                WHEN chat.chat_owner_type = '4' THEN M.fullname
                                WHEN chat.chat_owner_type = '3' THEN SA.fullname
                                WHEN chat.chat_owner_type = '2' THEN A.fullname
                                ELSE T.fullname
                            END AS fullname,
                            CASE 
                                WHEN latest_thread.receiver_type = '4' THEN MM.fullname
                                WHEN latest_thread.receiver_type = '3' THEN SSA.fullname
                                WHEN latest_thread.receiver_type = '2' THEN AA.fullname
                                ELSE TT.fullname
                            END AS sender_name,
                            latest_thread.message AS last_message,
                            latest_thread.date_created AS chat_sent,
                            latest_thread.message_status,
                            latest_thread.receiver_type,
                            latest_thread.receiver_id,
                            latest_thread.seen_status
                            FROM `chat`
                            LEFT JOIN (
                                SELECT ct.*
                                FROM chat_thread ct
                                INNER JOIN (
                                    SELECT chat_id, MAX(chat_thread_id) AS max_id
                                    FROM chat_thread
                                    GROUP BY chat_id
                                ) AS latest ON ct.chat_id = latest.chat_id AND ct.chat_thread_id = latest.max_id
                            ) AS latest_thread ON latest_thread.chat_id = chat.chat_id
                            LEFT JOIN members AS M ON chat.chat_owner = M.user_id
                            LEFT JOIN administrators AS A ON chat.chat_owner = A.user_id
                            LEFT JOIN super_administrators AS SA ON chat.chat_owner = SA.user_id
                            LEFT JOIN technicians AS T ON chat.chat_owner = T.user_id
                            LEFT JOIN members AS MM ON latest_thread.receiver_id = MM.user_id
                            LEFT JOIN administrators AS AA ON latest_thread.receiver_id = AA.user_id
                            LEFT JOIN super_administrators AS SSA ON latest_thread.receiver_id = SSA.user_id
                            LEFT JOIN technicians AS TT ON latest_thread.receiver_id = TT.user_id
                            WHERE chat_status = 'active' AND (SELECT `message` FROM `chat_thread` WHERE `chat_id` = chat.chat_id ORDER BY chat_thread_id DESC LIMIT 1) != 'NULL'
                            ORDER BY chat_sent DESC");
        $count = 0;
        $message_status = $request->input("entity") == "member" ? "received" : "sent";
        foreach($chats as $key => $chat){
            if($chat->seen_status == "notseen" && $chat->message_status == $message_status){
                $count++;
            }

            $difference = $this->getDateDifference($chat->chat_sent, date("YmdHis"));
            $chats[$key]->chat_sent = $difference['minutes'] < 60 ? number_format($difference['minutes'])."mins ago" : ($difference['days'] < 1 ? date("h:iA", strtotime($chat->chat_sent)) : ($difference['days'] < 7 ? date("D", strtotime($chat->chat_sent)) : date("dS-M", strtotime($chat->chat_sent))));
            $chats[$key]->selected = false;
        }

        // notification count
        return response()->json(["success" => true, "notification_count" => $count]);
    }

    function memberNotificationCount(Request $request){
        // send the message but first get the sender id and type using the token
        $authentication_code = $request->header('maru-authentication_code');
        $check_authentication_code = DB::select("SELECT * FROM `credentials` WHERE `authentication_code` = ?", [$authentication_code]);
        $member_id = count($check_authentication_code) > 0 ? $check_authentication_code[0]->user_id : "0";
        $chats = DB::select("SELECT chat.*,
                            CASE 
                                WHEN chat.chat_owner_type = '4' THEN M.fullname
                                WHEN chat.chat_owner_type = '3' THEN SA.fullname
                                WHEN chat.chat_owner_type = '2' THEN A.fullname
                                ELSE T.fullname
                            END AS fullname,
                            CASE 
                                WHEN latest_thread.receiver_type = '4' THEN MM.fullname
                                WHEN latest_thread.receiver_type = '3' THEN SSA.fullname
                                WHEN latest_thread.receiver_type = '2' THEN AA.fullname
                                ELSE TT.fullname
                            END AS sender_name,
                            latest_thread.message AS last_message,
                            latest_thread.date_created AS chat_sent,
                            latest_thread.message_status,
                            latest_thread.receiver_type,
                            latest_thread.receiver_id,
                            latest_thread.seen_status
                            FROM `chat`
                            LEFT JOIN (
                                SELECT ct.*
                                FROM chat_thread ct
                                INNER JOIN (
                                    SELECT chat_id, MAX(chat_thread_id) AS max_id
                                    FROM chat_thread
                                    GROUP BY chat_id
                                ) AS latest ON ct.chat_id = latest.chat_id AND ct.chat_thread_id = latest.max_id
                            ) AS latest_thread ON latest_thread.chat_id = chat.chat_id
                            LEFT JOIN members AS M ON chat.chat_owner = M.user_id
                            LEFT JOIN administrators AS A ON chat.chat_owner = A.user_id
                            LEFT JOIN super_administrators AS SA ON chat.chat_owner = SA.user_id
                            LEFT JOIN technicians AS T ON chat.chat_owner = T.user_id
                            LEFT JOIN members AS MM ON latest_thread.receiver_id = MM.user_id
                            LEFT JOIN administrators AS AA ON latest_thread.receiver_id = AA.user_id
                            LEFT JOIN super_administrators AS SSA ON latest_thread.receiver_id = SSA.user_id
                            LEFT JOIN technicians AS TT ON latest_thread.receiver_id = TT.user_id
                            WHERE chat_status = 'active' AND chat.chat_owner = '$member_id' AND (SELECT `message` FROM `chat_thread` WHERE `chat_id` = chat.chat_id ORDER BY chat_thread_id DESC LIMIT 1) != 'NULL'
                            ORDER BY chat_sent DESC");
        $count = 0;
        $message_status = $request->input("entity") == "member" ? "received" : "sent";
        foreach($chats as $key => $chat){
            if($chat->seen_status == "notseen" && $chat->message_status == $message_status){
                $count++;
            }

            $difference = $this->getDateDifference($chat->chat_sent, date("YmdHis"));
            $chats[$key]->chat_sent = $difference['minutes'] < 60 ? number_format($difference['minutes'])."mins ago" : ($difference['days'] < 1 ? date("h:iA", strtotime($chat->chat_sent)) : ($difference['days'] < 7 ? date("D", strtotime($chat->chat_sent)) : date("dS-M", strtotime($chat->chat_sent))));
            $chats[$key]->selected = false;
        }

        // notification count
        return response()->json(["success" => true, "notification_count" => $count]);
    }
}
