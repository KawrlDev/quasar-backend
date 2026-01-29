<?php

namespace App\Http\Controllers;

use App\Models\PatientList;
use App\Models\PatientDetails;
use App\Models\ClientName;
use App\Models\PatientHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PatientController extends Controller
{

    public function addPatient(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $nullify = fn($value) => (is_null($value) || $value === '' || $value === 'null' || (is_string($value) && strtolower($value) === 'n/a')) ? null : $value;

            $patientID = $request->input('patient_id');
            $updatePatientInfo = $request->boolean('update_patient_info');

            if ($patientID == null) {
                // Create new patient
                $patient = PatientList::create([
                    'lastname'      => $request->input('lastname'),
                    'firstname'     => $request->input('firstname'),
                    'middlename'    => $nullify($request->input('middlename')),
                    'suffix'        => $nullify($request->input('suffix')),
                    'birthdate'     => $nullify($request->input('birthdate')),
                    'sex'           => $nullify($request->input('sex')),
                    'preference'    => $nullify($request->input('preference')),
                    'province'      => $nullify($request->input('province')),
                    'city'          => $nullify($request->input('city')),
                    'barangay'      => $nullify($request->input('barangay')),
                    'house_address' => $nullify($request->input('house_address')),
                ]);
                $patientID = $patient->patient_id;
            } elseif ($updatePatientInfo) {
                // Update existing patient info
                $patient = PatientList::where('patient_id', $patientID)->firstOrFail();
                $patient->update([
                    'lastname'      => $request->input('lastname'),
                    'firstname'     => $request->input('firstname'),
                    'middlename'    => $nullify($request->input('middlename')),
                    'suffix'        => $nullify($request->input('suffix')),
                    'birthdate'     => $nullify($request->input('birthdate')),
                    'sex'           => $nullify($request->input('sex')),
                    'preference'    => $nullify($request->input('preference')),
                    'province'      => $nullify($request->input('province')),
                    'city'          => $nullify($request->input('city')),
                    'barangay'      => $nullify($request->input('barangay')),
                    'house_address' => $nullify($request->input('house_address')),
                ]);
            }

            $hospitalBillInput = $request->input('hospital_bill');
            $hospitalBill = (is_null($hospitalBillInput) || $hospitalBillInput === '' || strtolower($hospitalBillInput) === 'null' || strtolower($hospitalBillInput) === 'n/a')
                ? null
                : (float) $hospitalBillInput;

            $patientHistory = PatientHistory::create([
                'patient_id'    => $patientID,
                'category'      => $request->input('category'),
                'partner'       => $nullify($request->input('partner')),
                'hospital_bill' => $hospitalBill,
                'issued_amount' => $nullify($request->input('issued_amount')),
                'issued_by'     => $nullify($request->input('issued_by')),
                'date_issued'   => $request->input('issued_at'),
            ]);

            if (!$request->boolean('is_checked')) {
                ClientName::create([
                    'gl_no'        => $patientHistory->gl_no,
                    'lastname'     => $request->input('client_lastname'),
                    'firstname'    => $request->input('client_firstname'),
                    'middlename'   => $nullify($request->input('client_middlename')),
                    'suffix'       => $nullify($request->input('client_suffix')),
                    'relationship' => $nullify($request->input('relationship')),
                ]);
            }

            // Return the gl_no in a JSON response
            return response()->json(['gl_no' => $patientHistory->gl_no]);
        });
    }

    public function existingPatientList(Request $request)
    {
        $existingPatient = DB::table('patient_list')->join('patient_history', 'patient_history.patient_id', '=', 'patient_list.patient_id')->where(function ($query) use ($request) {
            $query->where('patient_list.lastname', $request->input('lastname'))
                ->where('patient_list.firstname', $request->input('firstname'))
                ->when($request->filled('middlename'), fn($q) => $q->where('patient_list.middlename', $request->input('middlename')))
                ->when($request->filled('suffix'), fn($q) => $q->where('patient_list.suffix', $request->input('suffix')));
        })->select(
            'patient_list.patient_id',
            'patient_list.lastname',
            'patient_list.firstname',
            'patient_list.middlename',
            'patient_list.suffix',
            DB::raw('GROUP_CONCAT(patient_history.gl_no) as gl_numbers')
        )
            ->groupBy('patient_list.patient_id', 'patient_list.lastname', 'patient_list.firstname', 'patient_list.middlename', 'patient_list.suffix')
            ->get();
        return response()->json($existingPatient);
    }

    public function getPatients()
    {
        $patientList = DB::table('patient_list')
            ->join('patient_history', 'patient_history.patient_id', '=', 'patient_list.patient_id')
            ->select('patient_list.lastname', 'patient_list.firstname', 'patient_list.middlename', 'patient_list.suffix', 'patient_history.category', 'patient_history.gl_no', 'patient_history.date_issued')
            ->orderby('patient_history.gl_no', 'desc')->get();
        return response()->json($patientList);
    }

    public function filterByDate(Request $request)
    {
        $query = DB::table('patient_history')
            ->join('patient_list', 'patient_list.patient_id', '=', 'patient_history.patient_id')
            ->select(
                'patient_list.lastname',
                'patient_list.firstname',
                'patient_list.middlename',
                'patient_list.suffix',
                'patient_history.category',
                'patient_history.gl_no',
                'patient_history.date_issued'
            );

        if ($request->filled('date')) {
            $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');
            $query->whereDate('date_issued', $date);
        }

        if ($request->filled(['from', 'to'])) {
            $from = Carbon::createFromFormat('d/m/Y', $request->from)->startOfDay();
            $to   = Carbon::createFromFormat('d/m/Y', $request->to)->endOfDay();
            $query->whereBetween('date_issued', [$from, $to]);
        }

        return $query->orderBy('date_issued', 'asc')->get();
    }

    public function search(Request $request)
    {
        $search = trim($request->query('q'));

        if (!$search) {
            return DB::table('patient_list')
                ->join('patient_history', 'patient_history.patient_id', '=', 'patient_list.patient_id')
                ->select(
                    'patient_list.lastname',
                    'patient_list.firstname',
                    'patient_list.middlename',
                    'patient_list.suffix',
                    'patient_history.category',
                    'patient_history.gl_no',
                    'patient_history.date_issued'
                )
                ->orderBy('patient_history.date_issued', 'desc')
                ->get();
        }

        // Remove commas for full-name matching
        $searchNoComma = str_replace(',', '', $search);

        return DB::table('patient_list')
            ->join('patient_history', 'patient_history.patient_id', '=', 'patient_list.patient_id')
            ->where(function ($q) use ($search, $searchNoComma) {

                $q->whereRaw(
                    "CONCAT_WS(' ', patient_list.lastname, patient_list.firstname, patient_list.middlename, patient_list.suffix) = ?",
                    [$searchNoComma]
                )

                    ->orWhereRaw(
                        "CONCAT_WS(' ', patient_list.lastname, patient_list.firstname, patient_list.middlename, patient_list.suffix) LIKE ?",
                        ["%{$searchNoComma}%"]
                    )

                    ->orWhere('patient_list.lastname', 'LIKE', "%{$search}%")
                    ->orWhere('patient_list.firstname', 'LIKE', "%{$search}%")
                    ->orWhere('patient_list.middlename', 'LIKE', "%{$search}%")
                    ->orWhere('patient_list.suffix', 'LIKE', "%{$search}%")

                    ->orWhere('patient_history.category', 'LIKE', "%{$search}%")
                    ->orWhere('patient_history.gl_no', 'LIKE', "%{$search}%");
            })
            ->select(
                'patient_list.lastname',
                'patient_list.firstname',
                'patient_list.middlename',
                'patient_list.suffix',
                'patient_history.category',
                'patient_history.gl_no',
                'patient_history.date_issued'
            )
            ->orderByRaw("
            CASE
                WHEN CONCAT_WS(' ', patient_list.lastname, patient_list.firstname, patient_list.middlename, patient_list.suffix) = ? THEN 1
                WHEN CONCAT_WS(' ', patient_list.lastname, patient_list.firstname, patient_list.middlename, patient_list.suffix) LIKE ? THEN 2
                WHEN patient_history.category = ? THEN 3
                WHEN patient_history.gl_no = ? THEN 4
                ELSE 5
            END
        ", [$searchNoComma, "%{$searchNoComma}%", $search, $search])
            ->orderBy('patient_list.lastname')
            ->orderBy('patient_list.firstname')
            ->orderBy('patient_history.gl_no', 'desc')
            ->get();
    }

    public function getPatientDetails($glNum)
    {
        $row = DB::table('patient_history')
            ->join('patient_list', 'patient_list.patient_id', '=', 'patient_history.patient_id')
            ->leftJoin('client_name', 'client_name.gl_no', '=', 'patient_history.gl_no')
            ->where('patient_history.gl_no', $glNum)
            ->select(
                // GL info
                'patient_history.gl_no',
                'patient_history.category',
                'patient_history.date_issued',

                // Patient name
                'patient_list.patient_id',
                'patient_list.lastname as patient_lastname',
                'patient_list.firstname as patient_firstname',
                'patient_list.middlename as patient_middlename',
                'patient_list.suffix as patient_suffix',

                // Patient details from patient_list
                'patient_list.birthdate',
                'patient_list.sex',
                'patient_list.preference',
                'patient_list.province',
                'patient_list.city',
                'patient_list.barangay',
                'patient_list.house_address',

                // Patient history details
                'patient_history.partner',
                'patient_history.hospital_bill',
                'patient_history.issued_amount',
                'patient_history.issued_by',

                // Client info (optional)
                'client_name.lastname as client_lastname',
                'client_name.firstname as client_firstname',
                'client_name.middlename as client_middlename',
                'client_name.suffix as client_suffix',
                'client_name.relationship'
            )
            ->first();

        return response()->json($row);
    }

    public function getPatientHistory($glNum)
    {
        // 1. Get the current GL record
        $current = DB::table('patient_history')
            ->where('gl_no', $glNum)
            ->select('patient_id', 'date_issued')
            ->first();

        if (!$current) {
            return response()->json(['message' => 'GL number not found'], 404);
        }

        // 2. Compute eligibility date (3 months after date_issued)
        $eligibilityDate = Carbon::parse($current->date_issued)
            ->addMonthsNoOverflow(3);

        // 3. Get ALL history for this patient via patient_id
        $history = DB::table('patient_history')
            ->where('patient_id', $current->patient_id)
            ->select(
                'gl_no',
                'category',
                'date_issued',
                'issued_by'
            )
            ->orderBy('gl_no', 'desc')
            ->get();

        return response()->json([
            'eligibility_date' => $eligibilityDate->toDateString(),
            'history' => $history
        ]);
    }

    public function updatePatientDetails(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $nullify = fn($value) => ($value === '' || $value === 'null' || strtolower($value) === 'n/a') ? null : $value;

            $glNum = $request->input('glNum');

            // Get the GL record (transaction)
            $history = PatientHistory::where('gl_no', $glNum)->firstOrFail();

            // Handle the 3 different scenarios based on flags
            if ($request->has('force_new_patient') && $request->input('force_new_patient') == '1') {
                // CREATE NEW PATIENT - create a new patient_list entry
                $newPatient = PatientList::create([
                    'lastname'      => $request->input('lastname'),
                    'firstname'     => $request->input('firstname'),
                    'middlename'    => $nullify($request->input('middlename')),
                    'suffix'        => $nullify($request->input('suffix')),
                    'birthdate'     => $nullify($request->input('birthdate')),
                    'sex'           => $nullify($request->input('sex')),
                    'preference'    => $nullify($request->input('preference')),
                    'province'      => $nullify($request->input('province')),
                    'city'          => $nullify($request->input('city')),
                    'barangay'      => $nullify($request->input('barangay')),
                    'house_address' => $nullify($request->input('house_address')),
                ]);

                // Update this GL to use the new patient_id
                $history->patient_id = $newPatient->patient_id;
            } elseif ($request->has('use_existing_patient_id')) {
                // USE EXISTING PATIENT - link to an existing patient_id
                $history->patient_id = $request->input('use_existing_patient_id');
            } else {
                // DEFAULT - update the current patient_list record (affects all GLs with this patient_id)
                $patient = PatientList::where('patient_id', $history->patient_id)->firstOrFail();
                $patient->update([
                    'lastname'      => $request->input('lastname'),
                    'firstname'     => $request->input('firstname'),
                    'middlename'    => $nullify($request->input('middlename')),
                    'suffix'        => $nullify($request->input('suffix')),
                    'birthdate'     => $nullify($request->input('birthdate')),
                    'sex'           => $nullify($request->input('sex')),
                    'preference'    => $nullify($request->input('preference')),
                    'province'      => $nullify($request->input('province')),
                    'city'          => $nullify($request->input('city')),
                    'barangay'      => $nullify($request->input('barangay')),
                    'house_address' => $nullify($request->input('house_address')),
                ]);
            }

            // Normalize hospital bill
            $hospitalBillInput = $request->input('hospital_bill');
            $hospitalBill = (is_null($hospitalBillInput) || $hospitalBillInput === '' ||
                strtolower($hospitalBillInput) === 'null' ||
                strtolower($hospitalBillInput) === 'n/a')
                ? null
                : (float) $hospitalBillInput;

            // Update GL-SPECIFIC details (patient_history)
            $history->update([
                'category'      => $request->input('category'),
                'partner'       => $nullify($request->input('partner')),
                'hospital_bill' => $hospitalBill,
                'issued_amount' => $nullify($request->input('issued_amount')),
                'issued_by'     => $nullify($request->input('issued_by')),
            ]);

            // Handle CLIENT (per-GL)
            $isChecked = $request->boolean('is_checked');

            if (!$isChecked) {
                ClientName::updateOrCreate(
                    ['gl_no' => $glNum],
                    [
                        'lastname'     => $request->input('client_lastname'),
                        'firstname'    => $request->input('client_firstname'),
                        'middlename'   => $nullify($request->input('client_middlename')),
                        'suffix'       => $nullify($request->input('client_suffix')),
                        'relationship' => $nullify($request->input('relationship')),
                    ]
                );
            } else {
                ClientName::where('gl_no', $glNum)->delete();
            }

            return response()->json(['success' => true]);
        });
    }

    // New endpoint: Update patient name across all records
    public function updatePatientName(Request $request)
    {
        $nullify = fn($value) => ($value === '' || $value === 'null' || strtolower($value) === 'n/a') ? null : $value;

        $patient = PatientList::where('patient_id', $request->input('patient_id'))->firstOrFail();

        $patient->update([
            'lastname'      => $request->input('lastname'),
            'firstname'     => $request->input('firstname'),
            'middlename'    => $nullify($request->input('middlename')),
            'suffix'        => $nullify($request->input('suffix')),
            'birthdate'     => $nullify($request->input('birthdate')),
            'sex'           => $nullify($request->input('sex')),
            'preference'    => $nullify($request->input('preference')),
            'province'      => $nullify($request->input('province')),
            'city'          => $nullify($request->input('city')),
            'barangay'      => $nullify($request->input('barangay')),
            'house_address' => $nullify($request->input('house_address')),
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteLetter($glNum)
    {
        $patient = PatientHistory::where('gl_no', $glNum)->firstOrFail()->delete();
        return response()->json(['success' => true]);
    }

    public function checkEligibility(Request $request)
    {
        // Find matching patient
        $patient = DB::table('patient_list')
            ->where('lastname', $request->input('lastname'))
            ->where('firstname', $request->input('firstname'))
            ->when($request->filled('middlename'), fn($q) => $q->where('middlename', $request->input('middlename')))
            ->when($request->filled('suffix'), fn($q) => $q->where('suffix', $request->input('suffix')))
            ->first();

        if (!$patient) {
            return response()->json(['eligible' => true]);
        }

        // Get the most recent GL record for this patient
        $latestRecord = DB::table('patient_history')
            ->where('patient_id', $patient->patient_id)
            ->orderBy('date_issued', 'desc')
            ->first();

        if (!$latestRecord) {
            return response()->json(['eligible' => true]);
        }

        // Calculate eligibility date (3 months after last date_issued)
        $eligibilityDate = Carbon::parse($latestRecord->date_issued)
            ->addMonthsNoOverflow(3);

        $today = Carbon::today();

        // Check if eligible
        if ($today->greaterThanOrEqualTo($eligibilityDate)) {
            return response()->json(['eligible' => true]);
        }

        // Not eligible yet
        return response()->json([
            'eligible' => false,
            'last_gl_no' => $latestRecord->gl_no,
            'last_issued_at' => $latestRecord->date_issued,
            'eligibility_date' => $eligibilityDate->toDateString(),
            'days_remaining' => $today->diffInDays($eligibilityDate, false)
        ]);
    }

    public function checkEligibilityById(Request $request)
    {
        $patientId = $request->input('patient_id');

        if (!$patientId) {
            return response()->json(['eligible' => true]);
        }

        // Get the most recent GL record for this patient
        $latestRecord = DB::table('patient_history')
            ->where('patient_id', $patientId)
            ->orderBy('date_issued', 'desc')
            ->first();

        if (!$latestRecord) {
            return response()->json(['eligible' => true]);
        }

        // Calculate eligibility date (3 months after last date_issued)
        $eligibilityDate = Carbon::parse($latestRecord->date_issued)
            ->addMonthsNoOverflow(3);

        $today = Carbon::today();

        // Check if eligible
        if ($today->greaterThanOrEqualTo($eligibilityDate)) {
            return response()->json(['eligible' => true]);
        }

        // Not eligible yet
        return response()->json([
            'eligible' => false,
            'last_gl_no' => $latestRecord->gl_no,
            'last_issued_at' => $latestRecord->date_issued,
            'eligibility_date' => $eligibilityDate->toDateString(),
            'days_remaining' => $today->diffInDays($eligibilityDate, false)
        ]);
    }

    public function getAllPatientsWithEligibility()
    {
        // Get all unique patients with their most recent GL record
        $patients = DB::table('patient_list')
            ->leftJoin('patient_history', function($join) {
                $join->on('patient_list.patient_id', '=', 'patient_history.patient_id')
                    ->whereRaw('patient_history.gl_no = (
                        SELECT MAX(ph2.gl_no) 
                        FROM patient_history ph2 
                        WHERE ph2.patient_id = patient_list.patient_id
                    )');
            })
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
                'patient_history.gl_no',
                'patient_history.category',
                'patient_history.date_issued as last_issued_at'
            )
            ->get();

        $today = Carbon::today();

        // Add eligibility information to each patient
        $patientsWithEligibility = $patients->map(function($patient) use ($today) {
            if (!$patient->last_issued_at) {
                // No previous GL records - eligible
                return array_merge((array)$patient, [
                    'eligible' => true,
                    'eligibility_date' => null,
                    'days_remaining' => null
                ]);
            }

            // Calculate eligibility date (3 months after last issued date)
            $eligibilityDate = Carbon::parse($patient->last_issued_at)
                ->addMonthsNoOverflow(3);

            $eligible = $today->greaterThanOrEqualTo($eligibilityDate);

            return array_merge((array)$patient, [
                'eligible' => $eligible,
                'eligibility_date' => $eligibilityDate->toDateString(),
                'days_remaining' => $eligible ? null : $today->diffInDays($eligibilityDate, false)
            ]);
        });

        return response()->json($patientsWithEligibility);
    }
}