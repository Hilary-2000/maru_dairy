<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\collectionLogs;
use App\Models\Credential;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\TestRunner\TestResult\Collector;

// set timezone
date_default_timezone_set('Africa/Nairobi');

class CollectionController extends Controller
{
    //get the collection stats on the different days
    function getCollection($period){
        
        // check period
        if ($period == "14 days") {
            $days = [];
            $prev_days = [];
            $counter = 0;
            $counter_2 = 14;
            $period_range = date("M-dS", strtotime(-$counter. " days"));
            $pr_first_day = date("M-dS", strtotime(-$counter. " days"));
            for($index = 0; $index < 7; $index++){
                $second_day = date("Ymd", strtotime(-$counter. " days"))."235959";
                $counter++;
                $first_day = date("Ymd", strtotime(-$counter. " days"))."000000";
                $pr_first_day = date("M-dS", strtotime($first_day));
                $counter++;

                // push the days
                array_push($days, [$first_day, $second_day]);

                // PREVIOUS 14 DAYS
                $second_day = date("Ymd", strtotime(-$counter_2. " days"))."235959";
                $counter_2++;
                $first_day = date("Ymd", strtotime(-$counter_2. " days"))."000000";
                $counter_2++;

                // push the days
                array_push($prev_days, [$first_day, $second_day]);
            }
            $period_range = $period_range." : ".$pr_first_day;

            // loop through days
            $collection_data = [];
            $total_litres = 0;
            $prev_total_litres = 0;
            for ($index=0; $index < count($days); $index++) {
                // current
                $users = Collection::selectRaw("SUM(`collection_amount`) AS total")
                        ->whereBetween('collection_date', [$days[$index][0], $days[$index][1]])
                        ->get();
                $data = ["day" => date("Ymd", strtotime($days[$index][0]))."-".date("Ymd", strtotime($days[$index][1])), "label" => date("M dS", strtotime($days[$index][1])), "amount" => (count($users)>0?$users[0]->total ?? 0:0), "litres" => (count($users)>0?$users[0]->total ?? 0:0)." Ltr"];
                $data = json_decode(json_encode($data));
                $total_litres += $data->amount;
                array_push($collection_data, $data);

                // PREVIOUS
                $users = Collection::selectRaw("SUM(`collection_amount`) AS total")
                        ->whereBetween('collection_date', [$prev_days[$index][0], $prev_days[$index][1]])
                        ->get();
                $prev_total_litres += (count($users)>0?$users[0]->total ?? 0:0);
            }

            // get the farmers
            $farmers = [];
            $total_farmers = 0;
            $prev_total_farmers = 0;
            for ($index=0; $index < count($days); $index++) { 
                $farmer = Member::selectRaw("COUNT(*) AS 'total'")
                        ->whereBetween('date_registered', [$days[$index][0], $days[$index][1]])
                        ->get();
                $data = ["day" => date("Ymd", strtotime($days[$index][0]))."-".date("Ymd", strtotime($days[$index][1])), "label" => date("M dS", strtotime($days[$index][1])), "farmers" => (count($farmer)>0?$farmer[0]->total ?? 0:0)];
                $data = json_decode(json_encode($data));
                $total_farmers += $data->farmers;
                array_push($farmers, $data);


                // PREVIOUS FARMER
                $farmer = Member::selectRaw("COUNT(*) AS 'total'")
                        ->whereBetween('date_registered', [$prev_days[$index][0], $prev_days[$index][1]])
                        ->get();
                $prev_total_farmers += (count($farmer)>0?$farmer[0]->total ?? 0:0);
            }

            $collection_status = $total_litres > $prev_total_litres ? "increase" : ($total_litres < $prev_total_litres ? "decrease" : "stagnant");
            $farmer_status = $total_farmers > $prev_total_farmers ? "increase" : ($total_farmers < $prev_total_farmers ? "decrease" : "stagnant");

            $collection_percentage = "0";
            if ($collection_status == "increase") {
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($total_litres - $prev_total_litres) / $prev_total_litres * 100)."" : "0";
            }else{
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($prev_total_litres - $total_litres) / $prev_total_litres * 100)."" : "0";
            }
            
            $farmer_percentage = "0";
            if ($farmer_status == "increase") {
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($total_farmers - $prev_total_farmers) / $prev_total_farmers * 100)."" : "0";
            }else{
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($prev_total_farmers - $total_farmers) / $prev_total_farmers * 100)."" : "0";
            }

            // return the response
            return response()->json(["success" => true, "data" => $collection_data, "total_litres" => round($total_litres, 2), "member" => $farmers, "total_farmers" => $total_farmers, "period_range" => $period_range, "farmer_status" => $farmer_status, "collection_status" => $collection_status, "collection_percentage" => round($collection_percentage, 2), "farmer_percentage" => round($farmer_percentage, 2)]);
        }elseif ($period == "30 days") {
            $days = [];
            $prev_days = [];
            $counter = 0;
            $counter_2 = 30;
            $period_range = date("M-dS", strtotime(-$counter. " days"));
            $pr_first_day = date("M-dS", strtotime(-$counter. " days"));
            for($index = 0; $index < 10; $index++){
                $second_day = date("Ymd", strtotime(-$counter. " days"))."235959";
                $counter+=2;
                $first_day = date("Ymd", strtotime(-$counter. " days"))."000000";
                $pr_first_day = date("M-dS", strtotime(-$counter. " days"));
                $counter+=1;

                // push the days
                array_push($days, [$first_day, $second_day]);

                // PREVIOUS 14 DAYS
                $second_day = date("Ymd", strtotime(-$counter_2. " days"))."235959";
                $counter_2+=2;
                $first_day = date("Ymd", strtotime(-$counter_2. " days"))."000000";
                $counter_2++;

                // push the days
                array_push($prev_days, [$first_day, $second_day]);
            }
            $period_range = $period_range." : ".$pr_first_day;

            // loop through days
            $collection_data = [];
            $total_litres = 0;
            $prev_total_litres = 0;
            for ($index=0; $index < count($days); $index++) {
                $users = Collection::selectRaw("SUM(`collection_amount`) AS total")
                        ->whereBetween('collection_date', [$days[$index][0], $days[$index][1]])
                        ->get();
                $data = ["day" => date("Ymd", strtotime($days[$index][0]))."-".date("Ymd", strtotime($days[$index][1])), "label" => date("M dS", strtotime($days[$index][1])), "amount" => (count($users)>0?$users[0]->total ?? 0:0), "litres" => ((count($users)>0?$users[0]->total ?? 0:0))." Ltr"];
                $data = json_decode(json_encode($data));
                $total_litres += $data->amount;
                array_push($collection_data, $data);

                // PREVIOUS DATA
                $users = Collection::selectRaw("SUM(`collection_amount`) AS total")
                        ->whereBetween('collection_date', [$prev_days[$index][0], $prev_days[$index][1]])
                        ->get();
                $prev_total_litres += (count($users)>0?$users[0]->total ?? 0:0);
            }

            // get the farmers
            $farmers = [];
            $total_farmers = 0;
            $prev_total_farmers = 0;
            for ($index=0; $index < count($days); $index++) {
                $farmer = Member::selectRaw("COUNT(*) AS 'total'")
                        ->whereBetween('date_registered', [$days[$index][0], $days[$index][1]])
                        ->get();
                $data = ["day" => date("Ymd", strtotime($days[$index][0]))."-".date("Ymd", strtotime($days[$index][1])), "label" => date("M dS", strtotime($days[$index][1])), "farmers" => (count($farmer)>0?$farmer[0]->total ?? 0:0)];
                $data = json_decode(json_encode($data));
                $total_farmers += $data->farmers;
                array_push($farmers, $data);

                //PREVIOUS
                $farmer = Member::selectRaw("COUNT(*) AS 'total'")
                        ->whereBetween('date_registered', [$prev_days[$index][0], $prev_days[$index][1]])
                        ->get();
                $prev_total_farmers += (count($farmer)>0?$farmer[0]->total ?? 0:0);
            }

            $collection_status = $total_litres > $prev_total_litres ? "increase" : ($total_litres < $prev_total_litres ? "decrease" : "stagnant");
            $farmer_status = $total_farmers > $prev_total_farmers ? "increase" : ($total_farmers < $prev_total_farmers ? "decrease" : "stagnant");

            $collection_percentage = "0";
            if ($collection_status == "increase") {
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($total_litres - $prev_total_litres) / $prev_total_litres * 100)."" : "0";
            }else{
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($prev_total_litres - $total_litres) / $prev_total_litres * 100)."" : "0";
            }
            
            $farmer_percentage = "0";
            if ($farmer_status == "increase") {
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($total_farmers - $prev_total_farmers) / $prev_total_farmers * 100)."" : "0";
            }else{
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($prev_total_farmers - $total_farmers) / $prev_total_farmers * 100)."" : "0";
            }

            // return the response
            return response()->json(["success" => true, "data" => $collection_data, "total_litres" => round($total_litres, 2), "member" => $farmers, "total_farmers" => round($total_farmers, 2), "period_range" => $period_range, "farmer_status" => $farmer_status, "collection_status" => $collection_status, "collection_percentage" => round($collection_percentage, 2), "farmer_percentage" => round($farmer_percentage, 2)]);
        }else{
            $days = [];
            $prev_days = [];
            for($index = 0; $index < 7; $index++){
                array_push($days, date("Ymd", strtotime(-$index." days")));
                array_push($prev_days, date("Ymd", strtotime((-$index-7)." days")));
            }
            $period_range = date("M-dS", strtotime($days[6]))." : ".date("M-dS", strtotime($days[0]));

            // loop through days
            $collection_data = [];
            $total_litres = 0;
            $prev_total_litres = 0;
            for ($index=0; $index < count($days); $index++) { 
                $users = Collection::selectRaw("SUM(`collection_amount`) AS total")
                        ->where('collection_date', 'LIKE', $days[$index].'%')
                        ->get();
                $data = ["day" => date("Ymd", strtotime($days[$index])), "label" => date("M-dS", strtotime($days[$index])), "amount" => count($users)>0?$users[0]->total ?? 0:0, "litres" => (count($users)>0?$users[0]->total ?? 0:0)." Ltr"];
                $data = json_decode(json_encode($data));
                $total_litres += $data->amount;
                array_push($collection_data, $data);

                //PREVIOUS COLLECTION
                $users = Collection::selectRaw("SUM(`collection_amount`) AS total")
                        ->where('collection_date', 'LIKE', $prev_days[$index].'%')
                        ->get();
                $prev_total_litres += count($users)>0?$users[0]->total ?? 0:0;
            }

            // get the farmers
            $farmers = [];
            $total_farmers = 0;
            $prev_total_farmers = 0;
            for ($index=0; $index < count($days); $index++) { 
                $farmer = Member::selectRaw("COUNT(*) AS 'total'")
                        ->where('date_registered', 'LIKE', $days[$index].'%')
                        ->get();
                $data = ["day" => date("Ymd", strtotime($days[$index])), "label" => date("M dS", strtotime($days[$index])), "farmers" => (count($farmer)>0?$farmer[0]->total ?? 0:0)];
                $data = json_decode(json_encode($data));
                $total_farmers += $data->farmers;
                array_push($farmers, $data);

                //PREVIOUS FARMER
                $farmer = Member::selectRaw("COUNT(*) AS 'total'")
                        ->where('date_registered', 'LIKE', $prev_days[$index].'%')
                        ->get();
                $prev_total_farmers += (count($farmer)>0?$farmer[0]->total ?? 0:0);
            }


            $collection_status = $total_litres > $prev_total_litres ? "increase" : ($total_litres < $prev_total_litres ? "decrease" : "stagnant");
            $farmer_status = $total_farmers > $prev_total_farmers ? "increase" : ($total_farmers < $prev_total_farmers ? "decrease" : "stagnant");

            $collection_percentage = "0";
            if ($collection_status == "increase") {
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($total_litres - $prev_total_litres) / $total_litres * 100)."" : "100";
            }else{
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($prev_total_litres - $total_litres) / $prev_total_litres * 100)."" : "0";
            }
            
            $farmer_percentage = "0";
            if ($farmer_status == "increase") {
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($total_farmers - $prev_total_farmers) / $prev_total_farmers * 100)."" : "100";
            }else{
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($prev_total_farmers - $total_farmers) / $prev_total_farmers * 100)."" : "0";
            }

            // return the response
            return response()->json(["success" => true, "data" => $collection_data, "total_litres" => round($total_litres, 2), "member" => $farmers, "total_farmers" => $total_farmers, "period_range" => $period_range,  "farmer_status" => $farmer_status, "collection_status" => $collection_status, "collection_percentage" => round($collection_percentage, 2), "farmer_percentage" => round($farmer_percentage, 2)]);
        }
    }

    // get the collection history of 7 days
    function getCollectionHistory(Request $request, $period){
        // $authentication_code = $request->header("maru-authentication_code");
        // $technician_id = 0;
        // $collection = DB::select("SELECT * FROM `credentials` WHERE `authentication_code` = '".$authentication_code."'");
        // if ($collection) {
        //     $technician_id = $collection[0]->user_id;
        // }

        $start = date("Ymd", strtotime("-7 days"))."000000";
        $end = date("Ymd")."235959";
        if ($period == "14 days") {
            $start = date("Ymd", strtotime("-14 days"))."000000";
            $end = date("Ymd")."235959";
        }elseif ($period == "30 days") {
            $start = date("Ymd", strtotime("-30 days"))."000000";
            $end = date("Ymd")."235959";
        }elseif ($period == "60 days") {
            $start = date("Ymd", strtotime("-60 days"))."000000";
            $end = date("Ymd")."235959";
        }
        $collection_history = DB::select("SELECT MC.*, M.fullname FROM `milk_collections` AS MC LEFT JOIN `members` AS M ON M.user_id = MC.member_id WHERE collection_date BETWEEN ? AND ? ORDER BY `collection_id` DESC", [$start, $end]);
        
        $confirmed = 0;
        $not_confirmed = 0;
        $rejected = 0;
        
        // go through to get the confirmed stats
        foreach ($collection_history as $key => $value) {
            $collection_history[$key]->date = date("D dS M Y", strtotime($value->collection_date));
            $collection_history[$key]->time = date("H:iA", strtotime($value->collection_date));
            if ($value->collection_status == 0) {
                $not_confirmed+=1;
            }elseif ($value->collection_status == 1) {
                $confirmed+=1;
            }else{
                $rejected+=1;
            }
        }

        // confirmed data
        return response()->json(["success" => true, "rejected" => $rejected, "confirmed" => $confirmed, "not_confirmed" => $not_confirmed, "collection_history" => $collection_history]);
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

    function collectionDetail($collection_id){
        $collection = DB::select("SELECT MC.*, M.fullname, M.membership, M.phone_number, M.user_id FROM `milk_collections` AS MC LEFT JOIN members AS M ON MC.member_id = M.user_id WHERE MC.collection_id = ?", [$collection_id]);
        
        if (count($collection) > 0) {
            $collection[0]->date = date("D dS M Y", strtotime($collection[0]->collection_date));
            $collection[0]->time = date("H:i A", strtotime($collection[0]->collection_date));

            $total_price = $this->getTotalPrice($collection);
            $collection[0]->price = number_format($total_price, 2);
            // get the edit history
            $member_id = $collection[0]->member_id;
            $collect_id = $collection[0]->collection_id;
            
            // edit_collection
            $edit_collection = DB::select("SELECT * FROM `collection_change_logs` WHERE `collection_id` = '".$collect_id."' ORDER BY `log_id` DESC");
            foreach ($edit_collection as $key => $value) {
                $collector_name = "N/A";
                if($value->user_type == "1"){
                    $collector = DB::select("SELECT * FROM `technicians` WHERE user_id = ?", [$value->user_change]);
                    $collector_name = count($collector) > 0 ? ucwords(strtolower($collector[0]->fullname)) : "N/A";
                }elseif ($value->user_type == "2") {
                    $collector = DB::select("SELECT * FROM `administrators` WHERE user_id = ?", [$value->user_change]);
                    $collector_name = count($collector) > 0 ? ucwords(strtolower($collector[0]->fullname)) : "N/A";
                }elseif ($value->user_type == "3") {
                    $collector = DB::select("SELECT * FROM `super_administrators` WHERE user_id = ?", [$value->user_change]);
                    $collector_name = count($collector) > 0 ? ucwords(strtolower($collector[0]->fullname)) : "N/A";
                }
                $edit_collection[$key]->collector_name = $collector_name;
                $edit_collection[$key]->full_date = date("D dS M Y", strtotime($value->date));
                $edit_collection[$key]->full_time = date("H:iA", strtotime($value->date));
            }


            // return values
            return response()->json(["success" => true, "collection" => $collection[0], "collection_history" => $edit_collection]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid collection, maybe it`s deleted!"]);
        }
    }

    // update milk
    function updateMilkCollection(Request $request){
        $date = date("YmdHis");
        $collection = DB::select("SELECT * FROM `milk_collections` WHERE `collection_id` = '".$request->input("collection_id") ."'");
        if (count($collection) == 0) {
            return response()->json(["success" => false, "message" => "Collection is Invalid!"]);
        }
        $update = DB::update("UPDATE `milk_collections` SET `collection_amount` = ? WHERE `collection_id` = ?",[$request->input("collection_amount"), $request->input("collection_id")]);

        // token
        $authentication_code = $request->header('maru-authentication_code');
        $credential = DB::select("SELECT * FROM `credentials` WHERE `authentication_code` = ?", [$authentication_code]);
        if (count($credential) == 0) {
            return response()->json(["success" => false, "message" => "Invalid token!"]);
        }

        // update the milk and record all the times it has been changed
        $collection_log = new collectionLogs();
        $collection_log->reading = $request->input("collection_amount");
        $collection_log->user_change = $credential[0]->user_id;
        $collection_log->user_type = $credential[0]->user_type;
        $collection_log->collection_id = $collection[0]->collection_id;
        $collection_log->date = date("YmdHis");
        $collection_log->save();
        
        // send the confirmation message
        return response()->json(["success" => true, "message" => "Collection amount updated successfully!"]);
    }

    function collectionHistory(Request $request){
        // collection
        $collection_period = $request->input("collection_period");
        $collection_status = $request->input("collection_status");

        $start = date("Ymd", strtotime("-7 days"))."000000";
        $end = date("Ymd")."235959";
        if ($collection_period == "14 days") {
            $start = date("Ymd", strtotime("-14 days"))."000000";
            $end = date("Ymd")."235959";
        }elseif ($collection_period == "30 days") {
            $start = date("Ymd", strtotime("-30 days"))."000000";
            $end = date("Ymd")."235959";
        }elseif ($collection_period == "60 days") {
            $start = date("Ymd", strtotime("-60 days"))."000000";
            $end = date("Ymd")."235959";
        }

        // collection history
        $collection_history = DB::select("SELECT * FROM `milk_collections` AS MC
                                        LEFT JOIN members AS M
                                        ON MC.member_id = M.user_id WHERE MC.collection_status = ? AND MC.collection_date BETWEEN ? AND ? ORDER BY MC.collection_id DESC", [$collection_status, $start, $end]);
        
        // go through to get the confirmed stats
        foreach ($collection_history as $key => $value) {
            $collection_history[$key]->date = date("D dS M Y", strtotime($value->collection_date));
            $collection_history[$key]->time = date("H:iA", strtotime($value->collection_date));
        }
        // RETURN VALUE
        return response()->json(["success" => true, "collection_history" => $collection_history]);
    }

    function deleteCollection($collection_id){
        $collection = DB::select("SELECT * FROM `milk_collections` WHERE `collection_id` = ?", [$collection_id]);
        if(count($collection)){
            $delete = DB::delete("DELETE FROM `milk_collections` WHERE `collection_id` = ?", [$collection_id]);
            $delete_log = DB::delete("DELETE FROM `collection_change_logs` WHERE `collection_id` = ?", [$collection_id]);
            return response()->json(["success" => true, "message" => "Collection has been deleted successfully!"]);
        }else{
            return response()->json(["success" => false, "message" => "Collection not found, It could be deleted!"]);
        }
    }
}
