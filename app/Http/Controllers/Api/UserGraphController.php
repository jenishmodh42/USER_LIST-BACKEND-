<?php

namespace App\Http\Controllers\Api;

use DateTime;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UserGraphController extends Controller
{
    public function userGraph(Request $request)
   {
    $type       = $request->type;        // day | week | month | year
    $year       = $request->year;
    $month      = $request->month;
    $startDate  = $request->start_date;
    $endDate    = $request->end_date;

    $labels = [];
    $counts = [];

    // Custom date range condition
    $query = User::query();
if ($startDate && $endDate) {
    $start = (new DateTime($startDate))->format('Y-m-d 00:00:00');
    $end   = (new DateTime($endDate))->format('Y-m-d 23:59:59');

    $query->whereBetween('created_at', [$start, $end]);
}

    switch ($type) {

    // ================= DAY WISE =================
    case 'day':

        $users = $query->select(
                DB::raw("COUNT(id) as total"),
                DB::raw("DATE(created_at) as day")
            )
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy("day")
            ->get()
            ->keyBy('day');   // day ko key bana diya

        // Date range loop (continuous days ke liye)
        $start = new DateTime($startDate);
        $end   = new DateTime($endDate);

        while ($start <= $end) {
            $day = $start->format('Y-m-d');

            $labels[] = $start->format('d M');   // 05 Jan, 06 Jan ...
            $counts[] = isset($users[$day]) ? $users[$day]->total : 0;

            $start->modify('+1 day');
        }

        break;

        // ================= WEEK WISE =================
       case 'week':

    if ($year)  $query->whereYear('created_at', $year);
    if ($month) $query->whereMonth('created_at', $month);

    $users = $query->select(
            DB::raw("COUNT(id) as total"),
            DB::raw("
                WEEK(created_at, 1) 
                - WEEK(DATE_SUB(created_at, INTERVAL DAYOFMONTH(created_at)-1 DAY), 1) 
                + 1 as week_of_month
            ")
        )
        ->groupBy(DB::raw("
            WEEK(created_at, 1) 
            - WEEK(DATE_SUB(created_at, INTERVAL DAYOFMONTH(created_at)-1 DAY), 1) 
            + 1
        "))
        ->orderBy("week_of_month")
        ->get()
        ->keyBy('week_of_month');   // week ko key bana diya

    // Total weeks in month calculate karo
    $targetYear = $year ?? date('Y');
    $targetMonth = $month ?? date('m');
    
    // Month ki first day aur last day
    $firstDay = new DateTime("$targetYear-$targetMonth-01");
    $lastDay = new DateTime("$targetYear-$targetMonth-01");
    $lastDay->modify('last day of this month');
    
    // Total weeks in month (1 to 5/6)
    $totalWeeks = ceil($lastDay->format('d') / 7);
    
    // All weeks ke liye loop (1 se total weeks tak)
    for ($week = 1; $week <= $totalWeeks; $week++) {
        $labels[] = "Week " . $week;
        $counts[] = isset($users[$week]) ? $users[$week]->total : 0;
    }

    break;

        // ================= MONTH WISE =================
       case 'month':

    // Agar year diya ho to filter karo
    if ($year) {
        $query->whereYear('created_at', $year);
    }

    $users = $query->select(
            DB::raw("COUNT(id) as total"),
            DB::raw("YEAR(created_at) as year"),
            DB::raw("MONTH(created_at) as month")
        )
        ->groupBy(
            DB::raw("YEAR(created_at)"),
            DB::raw("MONTH(created_at)")
        )
        ->orderBy(DB::raw("YEAR(created_at)"))     // pehle year
        ->orderBy(DB::raw("MONTH(created_at)"))    // phir month
        ->get();

    foreach ($users as $user) {
        $labels[] = date("M", mktime(0, 0, 0, $user->month, 1)) . " " . $user->year;
        $counts[] = $user->total;
    }

    break;

        // ================= YEAR WISE =================
        case 'year':
        default:
            $users = $query->select(
                    DB::raw("COUNT(id) as total"),
                    DB::raw("YEAR(created_at) as year")
                )
                ->groupBy(DB::raw("YEAR(created_at)"))
                ->orderBy("year")
                ->get();

            foreach ($users as $user) {
                $labels[] = (string)$user->year;
                $counts[] = $user->total;
            }
            break;
    }

    return response()->json([
        "status" => true,
        "labels" => $labels,
        "counts" => $counts
    ]);
   }
}