<?php

namespace App\Http\Controllers;

use App\Models\Regions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegionController extends Controller
{
    // get regions
    function getRegions(){
        $regions = DB::select("SELECT * FROM `regions` ORDER BY `region_name` ASC");
        if (count($regions) > 0) {
            return response()->json(["success" => true, "regions" => $regions]);
        }else{
            return response()->json(["success" => false, "message" => "Regions not found!"]);
        }
    }

    // update region
    function updateRegion(Request $request){
        $region_name = $request->input("region_name");
        $region_id = $request->input("region_id");
        $update = DB::update("UPDATE `regions` SET `region_name` = ? WHERE `region_id` = ?", [$region_name, $region_id]);
        return response()->json(["success" => true, "message" => "Region updated successfully!"]);
    }

    // delete region
    function deleteRegion($region_id){
        $delete = DB::delete("DELETE FROM `regions` WHERE `region_id` = ?", [$region_id]);
        return response()->json(["success" => true, "message" => "Region deleted successfully!"]);
    }

    function addRegion(Request $request){
        $regions = new Regions();
        $regions->region_name = $request->input("region_name");
        $regions->save();

        // regions added successfully!
        return response()->json(["success" => true, "message" => "Region added successfully!"]);
    }

    function updateRegionStatus(Request $request){
        // input
        $region_id = $request->input("region_id");
        $region_status = $request->input("region_status");


        // update region
        $regions = Regions::find($region_id);
        if ($regions != null) {
            $regions->status = $region_status;
            $regions->save();
            return response()->json(["success" => true, "message" => "Region status updated successfully!!"]);
        }else{
            return response()->json(["success" => false, "message" => "Invalid region!"]);
        }
    }

    function getActiveRegions(){
        $regions = DB::select("SELECT * FROM `regions` WHERE `status` = '1' ORDER BY `region_name` ASC;");
        if(count($regions) > 0){
            $array = array("region_id" => "", "region_name" => "Select your region");
            array_unshift($regions, $array);
            return response()->json(["success" => true, "regions" => $regions]);
        }else {
            return response()->json(["success" => false, "message" => "No regions present!"]);
        }
    }
}
