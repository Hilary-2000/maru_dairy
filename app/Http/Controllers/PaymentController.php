<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Classes\reports\PDF;
use App\Classes\reports\PDF2;
use App\Models\Member;

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

            // deductions
            $deduction_type = DB::select("SELECT * FROM `deduction_type` ORDER BY `deduction_name` ASC");
            
            return response()->json(["success" => true, "payment" => $payment, "deduction_type" => $deduction_type]);
        }else{
            return response()->json(["success" => "false", "message" => "Payment not found, maybe its deleted!"]);
        }
    }

    public static function getDeduction($payment_id){
        $payment = Payment::find($payment_id);
        if($payment){
            $deductions = DB::select("SELECT * FROM `deductions` WHERE `payment_id` = '".$payment_id."'");
            $total_deductions = 0;
            // sum the added deduction
            foreach ($deductions as $key => $deduct) {
                $total_deductions += $deduct->deduction_amount;
            }

            // sum the transaction fees
            if ($payment->deduct_transaction_fees == "yes") {
                $total_deductions += $payment->transaction_cost;
            }

            // payment
            return $payment->payment_amount - $total_deductions;
        }else{
            return 0;
        }
    }

    function paymentReceipt($payment_id){
        // payment
        $payment = DB::select("SELECT * FROM `payments` WHERE `payment_id` = ?",[$payment_id]);
        if (count($payment) > 0) {
            $member = Member::find($payment[0]->member_id);
            $deductions = DB::select("SELECT * FROM `deductions` WHERE `payment_id` = '".$payment_id."'");
            $pdf = new PDF2("P", "mm", [106, 200]);
            $pdf->setHeaderPos(5);
            $pdf->set_document_title(($member != null ? ucwords(strtolower($member->fullname)) : "N/A") . " ".($member->membership ?? ""). " ".$payment[0]->month_paid_for);
            $pdf->AddPage();
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetMargins(5, 5);
            $pdf->AddFont("robotomonoa",'','RobotoMono-Regular.php');
            $pdf->AddFont("robotomonob",'','RobotoMono-Bold.php');
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->SetLineWidth(30);
            $pdf->Ln(10);
            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(96, 10, "Issued on: ".date("D dS M Y H:i:sA", strtotime($payment[0]->date_paid)), 0, 0, 'C', false);
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->Ln();
            $pdf->Image(public_path("border.png"), 13, 19, 80);
            $pdf->SetFillColor(255, 255, 255);
            // Red color
            // Draw a rectangle (x, y, width, height) with the fill color
            $pdf->Rect(28, 42, 50, 5, 'F');
            $pdf->writeWithLetterSpacing($pdf, 33, $pdf->GetY(), "Membership No.", 1, 10);
            $pdf->Ln(12);
            $pdf->SetFont('robotomonob', '', 13);
            // $pdf->Cell(96, 10, "REG2024-021", 0, 0, 'C', false);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->writeWithLetterSpacing($pdf, 33, $pdf->GetY()-2, $member != null ? $member->membership :"N/A", 1, 10);
            $pdf->Ln(10);
            $pdf->Rect(5, 70, 96, 0.5, 'F');
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->SetXY(5,75);
            $pdf->Cell(50, 6,"Member Name:",);
            $pdf->Cell(50, 6,$member != null ? ucwords(strtolower($member->fullname)) :"N/A",);
            $pdf->Ln(6);
            $pdf->Cell(50, 6,"Member Contact:",);
            $pdf->Cell(50, 6, $member != null ? $member->phone_number :"N/A",);
            $pdf->Ln(6);
            $pdf->Cell(50, 6,"Region:",);
            $pdf->Cell(50, 6, $member != null ? $member->region :"N/A",);
            $pdf->Ln(6);
            $pdf->Cell(50, 6,"Litres Collected:",);
            $pdf->Cell(50, 6, $payment[0]->litres_amount." Litres",);
            $pdf->Ln(6);
            $pdf->Cell(50, 6,"Period:",);
            $pdf->Cell(50, 6,$payment[0]->month_paid_for,);
            $pdf->Ln(6);
            $pdf->Rect(5, 110, 96, 0.5, 'F');
            $pdf->SetXY(5,110);
            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(50, 10,"Gross Payment:",);
            $pdf->Cell(50, 10,"Kes ".number_format($payment[0]->payment_amount, 2),);
            $pdf->Ln(8);
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->Rect(5, 120, 96, 0.5, 'F');
            $pdf->SetXY(5,120);
            $total_deductions = 0;
            foreach ($deductions as $key => $deduction) {
                $pdf->Cell(50, 7,$this->deduction_type($deduction->deduction_type).":",);
                $pdf->Cell(50, 7,"Kes ". number_format($deduction->deduction_amount, 2),);
                $pdf->Ln(6);
                $total_deductions += $deduction->deduction_amount;
            }
            $total_deductions += $payment[0]->transaction_cost;
            $pdf->Cell(50, 7,"Transaction Cost:",);
            $pdf->Cell(50, 7,"Kes ". number_format($payment[0]->transaction_cost, 2),);
            $pdf->Ln(6);
            $pdf->SetFont('robotomonob', '', 10);
            $pdf->SetXY(5,150);
            $pdf->Rect(5, 150, 96, 0.5, 'F');
            $pdf->Cell(50, 10,"Total Deduction:",);
            $pdf->Cell(50, 10,"Kes ".number_format($total_deductions, 2),);
            $pdf->Ln(8);
            $pdf->Rect(5, 165, 96, 0.5, 'F');
            $pdf->SetXY(5,165);
            $pdf->Cell(50, 10,"Total Payment:",);
            $pdf->Cell(50, 10,"Kes ". number_format(($payment[0]->payment_amount - $total_deductions), 2),);
            $pdf->Ln();
            $pdf->SetFont('robotomonoa', '', 8);
            $pdf->Output();
        }else{
            return response()->json(["success" => false, "message" => "Invalid payment!"]);
        }
    }

    function deduction_type($deduction_type){
        // get the deduction type
        $deduction_type = DB::select("SELECT * FROM `deduction_type` WHERE `deduction_id` = ?", [$deduction_type]);
        if(count($deduction_type) > 0){
            return $deduction_type[0]->deduction_name;
        }

        // deduction type
        if($deduction_type == "transaction_cost"){
            return "Transaction Cost";
        }

        return "N/A";
    }
}
