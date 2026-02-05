<?php

namespace App\Http\Controllers;

use App\Models\PatientHistory;
use App\Models\PatientList;
use App\Models\YearlyBudget;
use App\Models\SupplementaryBonus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getTotalPatientsAndAmountReleased()
    {
        $totalPatients = PatientHistory::count();
        $totalAmount = PatientHistory::sum('issued_amount');

        return response()->json([
            'totalPatients' => $totalPatients,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function getCategoryData()
    {
        $year = Carbon::now()->year;

        $categories = ['MEDICINE', 'LABORATORY', 'HOSPITAL'];
        $data = [];

        foreach ($categories as $category) {
            $totalBudget = YearlyBudget::where('year', $year)->sum(strtolower($category) . '_budget') 
                         + SupplementaryBonus::sum(strtolower($category) . '_supplementary_bonus');

            $totalPatients = PatientHistory::where('category', $category)->count();
            $totalReleased = PatientHistory::where('category', $category)->sum('issued_amount');
            $remainingBal = $totalBudget - $totalReleased;

            $data[strtolower($category) . 'Data'] = [
                'totalBudget' => $totalBudget,
                'totalPatients' => $totalPatients,
                'totalReleased' => $totalReleased,
                'remaining' => $remainingBal,
            ];
        }

        return response()->json($data);
    }

    public function getAmountGiven()
    {
        // per category
        $categories = ['Medicine', 'Laboratory', 'Hospital'];
        $amountPerCategory = [];
        foreach ($categories as $category) {
            $amountPerCategory[strtolower($category)] = PatientHistory::where('category', $category)
                ->sum('issued_amount');
        }

        // per sex
        $amountPerSex = [
            'perMale' => PatientHistory::join('patient_list', 'patient_list.patient_id', '=', 'patient_history.patient_id')
                ->where('patient_list.sex', 'Male')
                ->sum('patient_history.issued_amount'),
            'perFemale' => PatientHistory::join('patient_list', 'patient_list.patient_id', '=', 'patient_history.patient_id')
                ->where('patient_list.sex', 'Female')
                ->sum('patient_history.issued_amount'),
        ];

        // per age bracket - calculate age from birthdate
        $ageBrackets = [
            '0to1' => [0, 1],
            '2to5' => [2, 5],
            '6to12' => [6, 12],
            '13to19' => [13, 19],
            '20to39' => [20, 39],
            '40to64' => [40, 64],
            '65AndAbove' => [65, null],
        ];

        $amountPerAge = [];
        foreach ($ageBrackets as $key => $range) {
            $query = PatientHistory::join('patient_list', 'patient_list.patient_id', '=', 'patient_history.patient_id')
                ->selectRaw('SUM(patient_history.issued_amount) as total')
                ->whereRaw('TIMESTAMPDIFF(YEAR, patient_list.birthdate, CURDATE()) >= ?', [$range[0]]);
            
            if ($range[1] !== null) {
                $query->whereRaw('TIMESTAMPDIFF(YEAR, patient_list.birthdate, CURDATE()) <= ?', [$range[1]]);
            }
            
            $result = $query->first();
            $amountPerAge[$key] = $result->total ?? 0;
        }

        return response()->json(array_merge($amountPerCategory, $amountPerSex, $amountPerAge));
    }

    public function getMonthlyPatients()
    {
        $year = date('Y');
        $categories = ['Medicine', 'Laboratory', 'Hospital'];
        $monthlyCounts = [];
        $totalCounts = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthTotal = 0;
            foreach ($categories as $category) {
                $count = PatientHistory::whereYear('date_issued', $year)
                    ->whereMonth('date_issued', $month)
                    ->where('category', $category)
                    ->count();

                $monthlyCounts[$category][$month] = $count;
                $monthTotal += $count;
            }
            $totalCounts[$month] = $monthTotal;
        }

        return response()->json([
            'monthlyCounts' => $monthlyCounts,
            'totalCounts' => $totalCounts
        ]);
    }

    public function getBarangayData()
    {
        $currentYear = Carbon::now()->year;

        $data = DB::table('patient_history')
            ->join('patient_list', 'patient_list.patient_id', '=', 'patient_history.patient_id')
            ->select(
                'patient_list.barangay',
                DB::raw("SUM(CASE WHEN patient_history.category = 'Medicine' THEN 1 ELSE 0 END) AS medicinePatients"),
                DB::raw("SUM(CASE WHEN patient_history.category = 'Laboratory' THEN 1 ELSE 0 END) AS laboratoryPatients"),
                DB::raw("SUM(CASE WHEN patient_history.category = 'Hospital' THEN 1 ELSE 0 END) AS hospitalPatients"),
                DB::raw("COUNT(patient_history.gl_no) AS totalPatients"),
                DB::raw("SUM(patient_history.issued_amount) AS totalAmount"),
                DB::raw("SUM(CASE WHEN patient_history.category = 'Medicine' THEN patient_history.issued_amount ELSE 0 END) AS medicineAmount"),
                DB::raw("SUM(CASE WHEN patient_history.category = 'Laboratory' THEN patient_history.issued_amount ELSE 0 END) AS laboratoryAmount"),
                DB::raw("SUM(CASE WHEN patient_history.category = 'Hospital' THEN patient_history.issued_amount ELSE 0 END) AS hospitalAmount")
            )
            ->whereYear('patient_history.date_issued', $currentYear)
            ->groupBy('patient_list.barangay')
            ->orderByDesc('totalAmount')
            ->get();

        return response()->json($data);
    }
}