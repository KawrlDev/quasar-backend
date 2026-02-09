<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\YearlyBudget;
use App\Models\SupplementaryBonus;
use Illuminate\Support\Facades\DB;

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

        $query = DB::table('patient_history')
            ->select(
                'category',
                DB::raw('MONTH(date_issued) as month'),
                DB::raw('SUM(issued_amount) as total_issued')
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

    /**
     * Validate if a budget transfer is possible without going negative
     */
    public function validateTransfer(Request $request)
    {
        $year = $request->input('year');
        $category = strtoupper($request->input('category'));
        $transferAmount = $request->input('amount');

        // Map category to budget field names
        $budgetField = $this->getCategoryBudgetField($category);
        $supplementalField = $this->getCategorySupplementalField($category);

        // 1. Get annual budget
        $yearlyBudget = YearlyBudget::where('year', $year)->first();
        $annualBudget = $yearlyBudget ? $yearlyBudget->$budgetField : 0;

        // 2. Get total supplemental bonuses for this category and year
        $totalSupplemental = SupplementaryBonus::where('year', $year)
            ->sum($supplementalField);

        // 3. Get total amount given (issued) for this category and year
        $totalGiven = DB::table('patient_history')
            ->where('category', $category)
            ->whereYear('date_issued', $year)
            ->sum('issued_amount');

        // 4. Calculate remaining budget after transfer
        $remaining = $annualBudget + $totalSupplemental - $totalGiven - $transferAmount;

        // 5. Build breakdown for frontend display
        $breakdown = [
            'annual' => $annualBudget,
            'supplemental' => $totalSupplemental,
            'given' => $totalGiven,
            'remaining' => $remaining
        ];

        // 6. Check if transfer is valid
        if ($remaining < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer amount is more than your remaining budget. Insufficient funds.',
                'breakdown' => $breakdown
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer is valid',
            'breakdown' => $breakdown
        ]);
    }

    /**
     * Helper function to get budget field name based on category
     */
    private function getCategoryBudgetField($category)
    {
        switch ($category) {
            case 'MEDICINE':
                return 'medicine_budget';
            case 'LABORATORY':
                return 'laboratory_budget';
            case 'HOSPITAL':
                return 'hospital_budget';
            default:
                return 'medicine_budget';
        }
    }

    /**
     * Helper function to get supplemental field name based on category
     */
    private function getCategorySupplementalField($category)
    {
        switch ($category) {
            case 'MEDICINE':
                return 'medicine_supplementary_bonus';
            case 'LABORATORY':
                return 'laboratory_supplementary_bonus';
            case 'HOSPITAL':
                return 'hospital_supplementary_bonus';
            default:
                return 'medicine_supplementary_bonus';
        }
    }
}