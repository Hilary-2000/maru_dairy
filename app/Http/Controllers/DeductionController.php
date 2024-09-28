<?php

namespace App\Http\Controllers;

use App\Models\DeductionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeductionController extends Controller
{
    //get the deductions
    function getDeductions(){
        $deductions = DB::select("SELECT * FROM `deduction_type` ORDER BY `deduction_name` ASC");
        if (count($deductions) > 0) {
            return response()->json(["success" => true, "deductions" => $deductions], 200);
        }else{
            return response()->json(["success" => false, "message" => "No deductions found!"], 200);
        }
    }

    function getActiveDeductions(){
        $deductions = DB::select("SELECT * FROM `deduction_type` WHERE `status` = '1' ORDER BY `deduction_name` ASC");
        if (count($deductions) > 0) {
            $deductiontype = $deductions;
            $default = array("deduction_id" => "", "deduction_name" => "Select deduction", "status" => "1");
            array_unshift($deductions, $default);
            return response()->json(["success" => true, "deductions" => $deductions, "deductiontype" => $deductiontype], 200);
        }else{
            return response()->json(["success" => false, "message" => "No deductions found!"], 200);
        }
    }

    function deleteDeductions($deduction_id){
        $delete = DB::delete("DELETE FROM `deduction_type` WHERE `deduction_id` = ?", [$deduction_id]);
        return response()->json(["success" => true, "message" => "Deduction has been deleted successfully!"], 200);
    }

    function updateDeductions(Request $request){
        $deduction_id = $request->input("deduction_id");
        $deduction_name = $request->input("deduction_name");
        $update = DB::update("UPDATE `deduction_type` SET `deduction_name` = ? WHERE `deduction_id` = ?", [$deduction_name, $deduction_id]);

        // success message
        return response()->json(["success" => true, "message" => "Deduction has been updated successfully!"], 200);
    }

    function addDeduction(Request $request){
        $deduction_type = new DeductionType();
        $deduction_type->deduction_name = $request->input("deduction_name");
        $deduction_type->save();
        
        // success message
        return response()->json(["success" => true, "message" => "Deduction has been added successfully!"], 200);
    }

    function updateDeductionStatus(Request $request){
        $deduction_type = DeductionType::find($request->deduction_id);
        $deduction_type->status = $request->input("deduction_status");
        $deduction_type->save();
        
        // success message
        return response()->json(["success" => true, "message" => "Deduction status has been changed successfully!"], 200);
    }
}
