<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GeneralSummaryController extends Controller
{
    /**
     * Get patient records with optional date filtering for general summary table
     */
    public function filterByDate(Request $request)
    {
        $query = DB::table('patient_list')
            ->join('patient_history', 'patient_history.patient_id', '=', 'patient_list.patient_id')
            ->select(
                'patient_list.patient_id',
                'patient_list.lastname',
                'patient_list.firstname',
                'patient_list.middlename',
                'patient_list.suffix',
                'patient_list.birthdate',
                'patient_list.sex',
                'patient_list.preference',
                'patient_list.province',
                'patient_list.city',
                'patient_list.barangay',
                'patient_list.house_address',
                'patient_list.phone_number',
                'patient_history.gl_no',
                'patient_history.category',
                'patient_history.partner',
                'patient_history.hospital_bill',
                'patient_history.issued_amount',
                'patient_history.issued_by',
                'patient_history.date_issued'
            );

        // Handle date filtering - EXACT DATE/RANGE, not month-based
        if ($request->has('from') && $request->has('to')) {
            // Date range filter
            $from = Carbon::createFromFormat('d/m/Y', $request->input('from'))->startOfDay();
            $to = Carbon::createFromFormat('d/m/Y', $request->input('to'))->endOfDay();
            
            \Log::info('Date Range Filter:', [
                'from_input' => $request->input('from'),
                'to_input' => $request->input('to'),
                'from_parsed' => $from->toDateTimeString(),
                'to_parsed' => $to->toDateTimeString()
            ]);
            
            $query->whereBetween('patient_history.date_issued', [$from, $to]);
        } elseif ($request->has('date')) {
            // Single date filter - EXACT DAY ONLY
            $date = Carbon::createFromFormat('d/m/Y', $request->input('date'));
            $dateStart = $date->copy()->startOfDay();
            $dateEnd = $date->copy()->endOfDay();
            
            \Log::info('Single Date Filter:', [
                'date_input' => $request->input('date'),
                'date_start' => $dateStart->toDateTimeString(),
                'date_end' => $dateEnd->toDateTimeString()
            ]);
            
            $query->whereBetween('patient_history.date_issued', [$dateStart, $dateEnd]);
        }

        $records = $query->orderBy('patient_history.date_issued', 'asc')
            ->orderBy('patient_list.lastname')
            ->get();

        return response()->json($records);
    }
}