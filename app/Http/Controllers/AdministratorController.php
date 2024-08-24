<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdministratorController extends Controller
{
    //GET THE ADMINISTRATOR ADMIN
    function admin_dashboard(Request $request, $period){
        // get the milk collection statistics
        $start = date("Ymd", strtotime(($period*-1)." days"))."000000";
        $end = date("Ymd", strtotime($period." days"))."235959";
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
        if($current_milk_collection > 0 && $prev_milk_collection > 0){
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
}
