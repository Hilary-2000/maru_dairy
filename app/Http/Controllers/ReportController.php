<?php

namespace App\Http\Controllers;

use App\Classes\reports\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    //generate report
    function generateReport(Request $request){
        $report_type = $request->input("report_type");
        $start_date = $request->input("start_date")."000000";
        $end_date = $request->input("end_date")."235959";
        $region = $request->input("region");

        if($report_type == "collections"){
            $str = $region == "0" ? "" : " AND M.region = '$region'";
            $collection = DB::select("SELECT MC.*, M.fullname AS member_fullname, M.membership FROM `milk_collections` AS MC
                            LEFT JOIN members AS M 
                            ON MC.member_id = M.user_id 
                            WHERE `collection_date` BETWEEN '".$start_date."' AND '".$end_date."' $str
                            ORDER BY `collection_id` DESC");
            

            // group_collection
            $group_collection = [];
            $total_cost = 0;
            $total_litres = 0;
            if (count($collection) > 0) {
                foreach ($collection as $key => $value) {
                    // return $value;
                    $collection_date = date("Ymd", strtotime($value->collection_date))."235959";
                    $collection_amount = $value->collection_amount;
                    
                    // get the milk price
                    $select = DB::select("SELECT * FROM `milk_prices` WHERE `effect_date` < '".$collection_date."' AND `status` = '1' ORDER BY `price_id` DESC LIMIT 1");
                    $price = (count($select) > 0 ? $select[0]->amount : 0) * $collection_amount;
                    $total_cost += $price;
                    $total_litres += $collection_amount;
                    $value->price = $price;
                    $value->collection_date = date("D dS M Y - H:iA", strtotime($value->collection_date));
                    array_push($group_collection, $value);
                }
            }

            // region name
            $region_data = DB::select("SELECT * FROM regions WHERE region_id = ?", [$region]);
            $region_name = $region == "0" ? "All regions" : (count($region_data) > 0 ? $region_data[0]->region_name ?? "NA" : "NA");
            
            $pdf = new PDF("P","mm","A4");
            $pdf->set_document_title("Collection between ".date("D dS M Y", strtotime($start_date))." to ".date("D dS M Y", strtotime($end_date))." ($region_name)");
            $pdf->AddPage();
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetMargins(10, 5);
            $pdf->AddFont("robotomonoa",'','RobotoMono-Regular.php');
            $pdf->AddFont("robotomonob",'','RobotoMono-Bold.php');
            $pdf->Ln();
            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(40, 10, "Total Payment:", 1, 0);
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->Cell(40, 10, "Kes ".number_format($total_cost, 2), 1, 1);

            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(40, 10, "Litres Collected:", 1, 0);
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->Cell(40, 10, number_format($total_litres, 2)." Litres", 1, 1);
            $pdf->Ln();
            // return $group_collection;
            
            // table header
            $pdf->SetFont('robotomonob', '', 9);
            $pdf->Cell(10,8, "#", 1, 0, "C");
            $pdf->Cell(25,8, "Litres", 1, 0, "C");
            $pdf->Cell(25,8, "Price", 1, 0, "C");
            $pdf->Cell(55,8, "Date", 1, 0, "C");
            $pdf->Cell(40,8, "Member", 1, 0, "C");
            $pdf->Cell(30,8, "Membeship No.", 1, 1, "L");
            $pdf->SetFont('robotomonoa', '', 9);
            $total_litres = 0;
            $total_collection = 0;
            foreach ($group_collection as $key => $value) {
                $pdf->Cell(10,8, ($key+1).".", 1, 0, "L");
                $pdf->Cell(25,8, $value->collection_amount." Ltrs", 1, 0, "C");
                $pdf->Cell(25,8, "Kes ".number_format($value->price, 2), 1, 0, "L");
                $pdf->Cell(55,8, $value->collection_date, 1, 0, "C");
                $pdf->Cell(40,8, ucwords(strtolower($value->member_fullname)), 1, 0, "L");
                $pdf->Cell(30,8, $value->membership, 1, 1, "L");
                $total_collection+= $value->price;
                $total_litres+= $value->collection_amount;
            }
            $pdf->SetFont('robotomonob', '', 8);
            $pdf->Cell(10,6, "Total", 1, 0, "L");
            $pdf->Cell(25,6, $total_litres." Ltrs", 1, 0, "R");
            $pdf->Cell(25,6, "Kes ".number_format($total_collection), 1, 0, "L");
            $pdf->Ln(10);
            $pdf->Output();
        }elseif ($report_type == "payments") {
            $str = $region == "0" ? "" : "WHERE region = '$region'";
            $members = DB::select("SELECT * FROM `members` $str ORDER BY `user_id` DESC");
            $total_cost = 0;
            $total_litres = 0;
            foreach ($members as $key => $member) {
                $collection = DB::select("SELECT * FROM `milk_collections` WHERE member_id = ? AND `collection_date` BETWEEN ? AND ?", [$member->user_id, $start_date, $end_date]);
                $price_collection = $this->getTotalPrice($collection);
                $total_cost += $price_collection[0];
                $total_litres += $price_collection[1];
                $members[$key]->total_price = $price_collection[0];
                $members[$key]->total_litres = $price_collection[1];
                $members[$key]->collection_days = count($collection);
            }
            
            // get members
            
            $pdf = new PDF("P","mm","A4");

            // region name
            $region_data = DB::select("SELECT * FROM regions WHERE region_id = ?", [$region]);
            $region_name = $region == "0" ? "All regions" : (count($region_data) > 0 ? $region_data[0]->region_name ?? "NA" : "NA");

            $pdf->set_document_title("Collection between ".date("D dS M Y", strtotime($start_date))." to ".date("D dS M Y", strtotime($end_date))." - ($region_name)");
            $pdf->AddPage();
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetMargins(10, 5);
            $pdf->AddFont("robotomonoa",'','RobotoMono-Regular.php');
            $pdf->AddFont("robotomonob",'','RobotoMono-Bold.php');
            $pdf->Ln();
            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(40, 10, "Total Payment:", 1, 0);
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->Cell(40, 10, "Kes ".number_format($total_cost, 2), 1, 1);

            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(40, 10, "Litres Collected:", 1, 0);
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->Cell(40, 10, number_format($total_litres, 2)." Litres", 1, 1);

            // table header
            $pdf->Ln();
            $pdf->SetFont('robotomonob', 'U', 10);
            $pdf->Cell(200,10, "Estimated Payment", 0, 1, "C");
            $pdf->SetFont('robotomonob', '', 9);
            $pdf->Cell(10,8, "#", 1, 0, "C");
            $pdf->Cell(55,8, "Member", 1, 0, "C");
            $pdf->Cell(35,8, "Membership", 1, 0, "C");
            $pdf->Cell(35,8, "Litres", 1, 0, "C");
            $pdf->Cell(30,8, "Price", 1, 0, "C");
            $pdf->Cell(30,8, "Records", 1, 1, "C");
            $pdf->SetFont('robotomonoa', '', 10);
            foreach ($members as $key => $member) {
                $pdf->Cell(10,7, ($key+1).".", 1, 0, "L");
                $pdf->Cell(55,7, ucwords(strtolower($member->fullname)), 1, 0, "L");
                $pdf->Cell(35,7, $member->membership, 1, 0, "L");
                $pdf->Cell(35,7, number_format($member->total_litres, 2), 1, 0, "L");
                $pdf->Cell(30,7, "Kes ".number_format($member->total_price), 1, 0, "L");
                $pdf->Cell(30,7, $member->collection_days, 1, 1, "L");
            }
            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(65,7, "", 0, 0, "L");
            $pdf->Cell(35,7, "Total", 1, 0, "L");
            $pdf->Cell(35,7, number_format($total_litres, 2), 1, 0, "L");
            $pdf->Cell(30,7, "Kes ".number_format($total_cost, 2), 1, 1, "L");
            $pdf->Output();
        }elseif ($report_type == "members") {
            $str = $region == "0" ? "" : "WHERE region = '$region'";
            $get_members = DB::select("SELECT members.*, regions.region_name FROM members LEFT JOIN regions ON members.region = regions.region_id $str ORDER BY `user_id` DESC");

            // region name
            $region_data = DB::select("SELECT * FROM regions WHERE region_id = ?", [$region]);
            $region_name = $region == "0" ? "All regions" : (count($region_data) > 0 ? $region_data[0]->region_name ?? "NA" : "NA");
            
            $pdf = new PDF("P","mm","A4");
            $pdf->set_document_title("All Members ($region_name)");
            $pdf->AddPage();
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetMargins(10, 5);
            $pdf->AddFont("robotomonoa",'','RobotoMono-Regular.php');
            $pdf->AddFont("robotomonob",'','RobotoMono-Bold.php');
            $pdf->Ln();
            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(40, 10, "No. of Members:", 1, 0);
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->Cell(40, 10, count($get_members)." members", 1, 1);

            // all members
            $pdf->SetFont('robotomonob', '', 9);
            $pdf->Ln();
            $pdf->Cell(7,8,"#",1,0,"C");
            $pdf->Cell(35,8,"Fullname",1,0,"C");
            $pdf->Cell(15,8,"Sex",1,0,"C");
            $pdf->Cell(25,8,"Membership",1,0,"C");
            $pdf->Cell(20,8,"Contact",1,0,"C");
            $pdf->Cell(18,8,"Nat I`d",1,0,"C");
            $pdf->Cell(28,8,"Joining Date",1,0,"C");
            $pdf->Cell(25,8,"Region",1,0,"C");
            $pdf->Cell(15,8,"Animal",1,1,"C");
            
            // loop through members
            $pdf->SetFont('robotomonoa', '', 8);
            foreach ($get_members as $key => $member) {
                $pdf->Cell(7,7,($key+1),1,0,"L");
                $pdf->Cell(35,7,ucwords(strtolower($member->fullname)),1,0,"L");
                $pdf->Cell(15,7,ucwords(strtolower($member->gender)),1,0,"L");
                $pdf->Cell(25,7,$member->membership,1,0,"L");
                $pdf->Cell(20,7,$member->phone_number,1,0,"L");
                $pdf->Cell(18,7,$member->national_id,1,0,"L");
                $pdf->Cell(28,7,date("dS M Y", strtotime($member->date_registered)),1,0,"L");
                $pdf->Cell(25,7,$member->region_name,1,0,"L");
                $pdf->Cell(15,7,$member->animals,1,1,"L");
            }
            $pdf->Output();
        }elseif ($report_type == "member_registration") {
            $str = $region == "0" ? "" : "AND region = '$region'";
            $get_members = DB::select("SELECT members.*, regions.region_name FROM `members` LEFT JOIN regions ON members.region = regions.region_id WHERE `date_registered` BETWEEN ? AND ? $str ORDER BY `user_id` DESC", [$start_date, $end_date]);
            

            // region name
            $region_data = DB::select("SELECT * FROM regions WHERE region_id = ?", [$region]);
            $region_name = $region == "0" ? "All regions" : (count($region_data) > 0 ? $region_data[0]->region_name ?? "NA" : "NA");

            $pdf = new PDF("P","mm","A4");
            $pdf->set_document_title("Members registered between ".date("D dS M Y", strtotime($start_date))." to ".date("D dS M Y", strtotime($end_date))." ($region_name)");
            $pdf->AddPage();
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetMargins(10, 5);
            $pdf->AddFont("robotomonoa",'','RobotoMono-Regular.php');
            $pdf->AddFont("robotomonob",'','RobotoMono-Bold.php');
            $pdf->Ln();
            $pdf->SetFont('robotomonob', '', 10);
            $pdf->Cell(40, 10, "No. of Members:", 1, 0);
            $pdf->SetFont('robotomonoa', '', 10);
            $pdf->Cell(40, 10, count($get_members)." members", 1, 1);

            // all members
            $pdf->SetFont('robotomonob', '', 9);
            $pdf->Ln();
            $pdf->Cell(7,8,"#",1,0,"C");
            $pdf->Cell(35,8,"Fullname",1,0,"C");
            $pdf->Cell(15,8,"Sex",1,0,"C");
            $pdf->Cell(25,8,"Membership",1,0,"C");
            $pdf->Cell(20,8,"Contact",1,0,"C");
            $pdf->Cell(18,8,"Nat I`d",1,0,"C");
            $pdf->Cell(28,8,"Joining Date",1,0,"C");
            $pdf->Cell(25,8,"Region",1,0,"C");
            $pdf->Cell(15,8,"Animal",1,1,"C");
            
            // loop through members
            $pdf->SetFont('robotomonoa', '', 8);
            foreach ($get_members as $key => $member) {
                $pdf->Cell(7,7,($key+1),1,0,"L");
                $pdf->Cell(35,7,ucwords(strtolower($member->fullname)),1,0,"L");
                $pdf->Cell(15,7,ucwords(strtolower($member->gender)),1,0,"L");
                $pdf->Cell(25,7,$member->membership,1,0,"L");
                $pdf->Cell(20,7,$member->phone_number,1,0,"L");
                $pdf->Cell(18,7,$member->national_id,1,0,"L");
                $pdf->Cell(28,7,date("dS M Y", strtotime($member->date_registered)),1,0,"L");
                $pdf->Cell(25,7,$member->region_name,1,0,"L");
                $pdf->Cell(15,7,$member->animals,1,1,"L");
            }
            $pdf->Output();
        }
    }

    function getTotalPrice($data){
        $total_price = 0;
        $total_litres = 0;
        foreach ($data as $key => $value) {
            $collection_date = date("Ymd", strtotime($value->collection_date))."000000";
            $collection_amount = $value->collection_amount;
            $total_litres += $value->collection_amount;

            // get the milk price
            $select = DB::select("SELECT * FROM `milk_prices` WHERE `effect_date` < '".$collection_date."' AND `status` = '1' ORDER BY `price_id` DESC LIMIT 1");
            $total_price += (count($select) > 0 ? $select[0]->amount : 0) * $collection_amount;
        }

        // return price
        return [$total_price, $total_litres];
    }
}
