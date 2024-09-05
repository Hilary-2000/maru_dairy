<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    //
    function payment_details($payment_id){
        $payment = Payment::find($payment_id);
        if($payment){
            $deductions = DB::select("SELECT * FROM `deductions` WHERE `payment_id` = '".$payment_id."'");
            $total_deductions = 0;
            foreach ($deductions as $key => $deduct) {
                $total_deductions += $deduct->deduction_amount;
                $deductions[$key]->deduction_amount = number_format($deductions[$key]->deduction_amount, 2);
            }

            if ($payment->deduct_transaction_fees == "yes") {
                $data = array(
                    "deduction_amount" => $payment->transaction_cost,
                    "deduction_type" => "transaction_cost",
                    "deduction_date" => date("D dS M Y : H:i:sA", strtotime($payment->date_paid)),
                );
                $total_deductions += $payment->transaction_cost;

                // data
                array_push($deductions, $data);
            }

            // payment
            $payment->total_payment = $payment->payment_amount - $total_deductions;
            $payment->deductions = $deductions;
            $payment->date_paid = date("D dS M Y : H:i:sA", strtotime($payment->date_paid));
            $payment->payment_amount = number_format($payment->payment_amount, 2);
            $payment->total_payment = number_format($payment->total_payment, 2);
            
            return response()->json(["success" => true, "payment" => $payment]);
        }else{
            return response()->json(["success" => "false", "message" => "Payment not found, maybe its deleted!"]);
        }
    }
}
