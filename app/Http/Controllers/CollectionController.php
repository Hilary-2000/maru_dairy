<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Member;
use Illuminate\Http\Request;
use PHPUnit\TestRunner\TestResult\Collector;

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
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($total_litres - $prev_total_litres) / $prev_total_litres * 100)."%" : "0%";
            }else{
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($prev_total_litres - $total_litres) / $prev_total_litres * 100)."%" : "0%";
            }
            
            $farmer_percentage = "0";
            if ($farmer_status == "increase") {
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($total_farmers - $prev_total_farmers) / $prev_total_farmers * 100)."%" : "0%";
            }else{
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($prev_total_farmers - $total_farmers) / $prev_total_farmers * 100)."%" : "0%";
            }

            // return the response
            return response()->json(["success" => true, "data" => $collection_data, "total_litres" => $total_litres, "member" => $farmers, "total_farmers" => $total_farmers, "period_range" => $period_range, "farmer_status" => $farmer_status, "collection_status" => $collection_status, "collection_percentage" => $collection_percentage, "farmer_percentage" => $farmer_percentage]);
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
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($total_litres - $prev_total_litres) / $prev_total_litres * 100)."%" : "0%";
            }else{
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($prev_total_litres - $total_litres) / $prev_total_litres * 100)."%" : "0%";
            }
            
            $farmer_percentage = "0";
            if ($farmer_status == "increase") {
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($total_farmers - $prev_total_farmers) / $prev_total_farmers * 100)."%" : "0%";
            }else{
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($prev_total_farmers - $total_farmers) / $prev_total_farmers * 100)."%" : "0%";
            }

            // return the response
            return response()->json(["success" => true, "data" => $collection_data, "total_litres" => $total_litres, "member" => $farmers, "total_farmers" => $total_farmers, "period_range" => $period_range, "farmer_status" => $farmer_status, "collection_status" => $collection_status, "collection_percentage" => $collection_percentage, "farmer_percentage" => $farmer_percentage]);
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
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($total_litres - $prev_total_litres) / $prev_total_litres * 100)."%" : "0%";
            }else{
                $collection_percentage = ($total_litres > 0 && $prev_total_litres > 0) ? (($prev_total_litres - $total_litres) / $prev_total_litres * 100)."%" : "0%";
            }
            
            $farmer_percentage = "0";
            if ($farmer_status == "increase") {
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($total_farmers - $prev_total_farmers) / $prev_total_farmers * 100)."%" : "0%";
            }else{
                $farmer_percentage = ($total_farmers > 0 && $prev_total_farmers > 0) ? (($prev_total_farmers - $total_farmers) / $prev_total_farmers * 100)."%" : "0%";
            }

            // return the response
            return response()->json(["success" => true, "data" => $collection_data, "total_litres" => $total_litres, "member" => $farmers, "total_farmers" => $total_farmers, "period_range" => $period_range,  "farmer_status" => $farmer_status, "collection_status" => $collection_status, "collection_percentage" => $collection_percentage, "farmer_percentage" => $farmer_percentage]);
        }
    }
}
