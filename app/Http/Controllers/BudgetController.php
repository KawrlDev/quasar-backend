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
}
