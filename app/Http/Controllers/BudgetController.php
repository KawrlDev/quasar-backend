<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\YearlyBudget;
use App\Models\SupplementaryBonus;

class BudgetController extends Controller
{
    public function createYearlyBudget(Request $request)
    {
        $year = $request->input('year');
        $yearlyBudget = YearlyBudget::where('year', $year)->first();
        if ($yearlyBudget) {
            $yearlyBudget->update([
                'medicine_budget' => $request->input('medicine_budget'),
                'laboratory_budget' => $request->input('laboratory_budget'),
                'hospital_budget' => $request->input('hospital_budget'),
            ]);
        } else {
            YearlyBudget::create([
                'year' => $year,
                'medicine_budget' => $request->input('medicine_budget'),
                'laboratory_budget' => $request->input('laboratory_budget'),
                'hospital_budget' => $request->input('hospital_budget'),
            ]);
        }
        return response()->json(['success' => true]);
    }
    public function getYearlyBudget()
    {
        $yearlyBudget = YearlyBudget::select('year', 'medicine_budget', 'laboratory_budget', 'hospital_budget')->orderby('year', 'desc')->get();
        return response()->json($yearlyBudget);
    }
    public function addSupplementaryBonus(Request $request)
    {
        SupplementaryBonus::create([
            'year' => $request->input('year'),
            'date_added' => $request->input('date_added'),
            'medicine_supplementary_bonus' => $request->input('medicine_supplementary_bonus'),
            'laboratory_supplementary_bonus' => $request->input('laboratory_supplementary_bonus'),
            'hospital_supplementary_bonus' => $request->input('hospital_supplementary_bonus'),
        ]);
        return response()->json(['success' => true]);
    }
    public function getSupplementaryBonus()
    {
        $supplementaryBonus = SupplementaryBonus::select('id', 'year', 'date_added', 'medicine_supplementary_bonus', 'laboratory_supplementary_bonus', 'hospital_supplementary_bonus')->orderby('id', 'desc')->get();
        return response()->json($supplementaryBonus);
    }

    public function getIssuedAmountByYear(Request $request)
    {
        $year = $request->input('year'); // e.g. 2026
        $category = $request->input('category'); // optional filter

        $query = \DB::table('patient_history')
            ->select(
                'category',
                \DB::raw('MONTH(date_issued) as month'),
                \DB::raw('SUM(issued_amount) as total_issued')
            )
            ->whereYear('date_issued', $year);

        if ($category) {
            $query->where('category', $category);
        }

        $result = $query->groupBy('category', 'month')->get();

        // Format output to something easy for your frontend to consume
        $output = [];

        foreach ($result as $row) {
            $cat = strtoupper($row->category);
            $mon = strtolower(date('M', mktime(0, 0, 0, $row->month, 10))); // jan, feb, etc.
            $output[$cat][$mon] = $row->total_issued;
        }

        return response()->json($output);
    }



}
