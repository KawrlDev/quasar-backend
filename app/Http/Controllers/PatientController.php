<?php

namespace App\Http\Controllers;

use App\Models\PatientList;
use App\Models\PatientDetails;
use App\Models\ClientName;
use App\Models\PatientHistory;
use App\Models\WebsiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PatientController extends Controller
{
    /**
     * Get the eligibility cooldown period in days from settings
     */
    private function getEligibilityCooldownDays()
    {
        return WebsiteSettings::where('id', 1)->value('eligibility_cooldown') ?? 90; // Default to 90 days if not set
    }

    private function syncPatientSectors(int $patientId, $sectorIdsJson): void
    {
        $sectorIds = json_decode($sectorIdsJson, true);
        if (!is_array($sectorIds)) return;

        DB::table('user_sectors')->where('patient_id', $patientId)->delete();

        if (count($sectorIds) > 0) {
            $inserts = array_map(fn($id) => [
                'patient_id' => $patientId,
                'sector_id'  => (int) $id,
            ], $sectorIds);
            DB::table('user_sectors')->insert($inserts);
        }
    }

    private function attachSectorIds($patients)
    {
        $patientIds = $patients->pluck('patient_id')->unique()->toArray();

        $patientSectors = DB::table('user_sectors')
            ->whereIn('patient_id', $patientIds)
            ->select('patient_id', 'sector_id')
            ->get()
            ->groupBy('patient_id')
            ->map(fn($sectors) => $sectors->pluck('sector_id')->toArray());

        foreach ($patients as $patient) {
            $patient->sector_ids = $patientSectors->get($patient->patient_id, []);
        }

        return response()->json($patients);
    }

    private function normalizePhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber) || $phoneNumber === 'null' || strtolower($phoneNumber) === 'n/a') {
            return null;
        }

        // Remove all non-digit characters
        $cleaned = preg_replace('/\D/', '', $phoneNumber);

        // Convert 63 prefix to 09
        if (substr($cleaned, 0, 2) === '63') {
            $cleaned = '0' . substr($cleaned, 2);
        }

        // Validate: must start with 09 and be exactly 11 digits
        if (substr($cleaned, 0, 2) !== '09' || strlen($cleaned) !== 11) {
            return null; // Invalid format
        }

        return $cleaned;
    }

    public function addPatient(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $nullify = fn($value) => (is_null($value) || $value === '' || $value === 'null' || (is_string($value) && strtolower($value) === 'n/a')) ? null : $value;

            $patientID = $request->input('patient_id');
            $updatePatientInfo = $request->boolean('update_patient_info');

            // Normalize and validate phone number
            $phoneNumber = $this->normalizePhoneNumber($request->input('phone_number'));

            if ($request->filled('phone_number') && $phoneNumber === null) {
                return response()->json([
                    'error' => 'Invalid phone number format. Must be 11 digits starting with 09 or +63'
                ], 422);
            }

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
                    'phone_number'  => $phoneNumber,
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
                    'phone_number'  => $phoneNumber,
                ]);
            }

            // Sync sectors if provided
            if ($request->filled('sector_ids')) {
                $this->syncPatientSectors($patientID, $request->input('sector_ids'));
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
                    'uuid'         => $patientHistory->uuid, // Changed from gl_no
                    'lastname'     => $request->input('client_lastname'),
                    'firstname'    => $request->input('client_firstname'),
                    'middlename'   => $nullify($request->input('client_middlename')),
                    'suffix'       => $nullify($request->input('client_suffix')),
                    'relationship' => $nullify($request->input('relationship')),
                ]);
            }

            // Return both uuid and gl_no in the response
            return response()->json([
                'uuid' => $patientHistory->uuid,
                'gl_no' => $patientHistory->gl_no
            ]);
        });
    }

    public function existingPatientList(Request $request)
    {
        $existingPatient = DB::table('patient_list')
            ->join('patient_history', 'patient_history.patient_id', '=', 'patient_list.patient_id')
            ->where(function ($query) use ($request) {
                $query->where('patient_list.lastname', $request->input('lastname'))
                    ->where('patient_list.firstname', $request->input('firstname'))
                    ->when($request->filled('middlename'), fn($q) => $q->where('patient_list.middlename', $request->input('middlename')))
                    ->when($request->filled('suffix'), fn($q) => $q->where('patient_list.suffix', $request->input('suffix')));
            })
            ->select(
                'patient_list.patient_id',
                'patient_list.lastname',
                'patient_list.firstname',
                'patient_list.middlename',
                'patient_list.suffix',
                DB::raw('GROUP_CONCAT(patient_history.uuid) as uuids'), // Changed from gl_numbers
                DB::raw('GROUP_CONCAT(patient_history.gl_no) as gl_numbers') // Keep for display
            )
            ->groupBy('patient_list.patient_id', 'patient_list.lastname', 'patient_list.firstname', 'patient_list.middlename', 'patient_list.suffix')
            ->get();

        return response()->json($existingPatient);
    }

    public function getPatients()
{
    $currentYear = now()->year;
    
    $patientList = DB::table('patient_list')
        ->join('patient_history', 'patient_history.patient_id', '=', 'patient_list.patient_id')
        ->select(
            'patient_list.patient_id',
            'patient_list.lastname',
            'patient_list.firstname',
            'patient_list.middlename',
            'patient_list.suffix',
            'patient_list.barangay',
            'patient_history.category',
            'patient_history.uuid',
            'patient_history.gl_no',
            'patient_history.date_issued'
        )
        ->whereYear('patient_history.date_issued', $currentYear) // Only current year
        ->orderBy('patient_history.gl_no', 'desc') // Latest GL first
        ->get();

    // Get all unique patient IDs
    $patientIds = $patientList->pluck('patient_id')->unique()->toArray();

    // Fetch sector IDs for all patients in one query
    $patientSectors = DB::table('user_sectors')
        ->whereIn('patient_id', $patientIds)
        ->select('patient_id', 'sector_id')
        ->get()
        ->groupBy('patient_id')
        ->map(function ($sectors) {
            return $sectors->pluck('sector_id')->toArray();
        });

    // Attach sector_ids to each result
    foreach ($patientList as $patient) {
        $patient->sector_ids = $patientSectors->get($patient->patient_id, []);
    }

    return response()->json($patientList);
}

public function search(Request $request)
{
    $search = trim($request->query('q'));
    $currentYear = now()->year;

    $baseQuery = DB::table('patient_list')
        ->join('patient_history', 'patient_history.patient_id', '=', 'patient_list.patient_id')
        ->leftJoin('user_sectors', 'user_sectors.patient_id', '=', 'patient_list.patient_id')
        ->leftJoin('sectors', 'sectors.id', '=', 'user_sectors.sector_id')
        ->select(
            'patient_list.patient_id',
            'patient_list.lastname',
            'patient_list.firstname',
            'patient_list.middlename',
            'patient_list.suffix',
            'patient_list.barangay',
            'patient_history.category',
            'patient_history.uuid',
            'patient_history.gl_no',
            'patient_history.date_issued'
        )
        ->distinct();

    // If no search term, show only current year
    if (!$search) {
        $results = $baseQuery
            ->whereYear('patient_history.date_issued', $currentYear)
            ->orderBy('patient_history.gl_no', 'desc')
            ->get();

        return $this->attachSectorIds($results);
    }

    // If searching by UUID (starts with MAMS-), search all years
    $isUuidSearch = strpos($search, 'MAMS-') === 0;
    
    // If it's a pure number and current year records exist with that GL, prioritize current year
    $isPureNumber = is_numeric($search);

    $searchNoComma = str_replace(',', '', $search);

    $query = $baseQuery->where(function ($q) use ($search, $searchNoComma, $isPureNumber, $currentYear) {
        // Name searches (highest priority)
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
        ->orWhere('patient_list.suffix', 'LIKE', "%{$search}%");
        
        // If it's a number, search GL number and only show exact match or current year results
        if ($isPureNumber) {
            $q->orWhere(function($subQ) use ($search, $currentYear) {
                $subQ->where('patient_history.gl_no', '=', $search)
                     ->whereYear('patient_history.date_issued', $currentYear);
            });
        } else {
            // For non-numeric searches, include other fields
            $q->orWhere('patient_list.barangay', 'LIKE', "%{$search}%")
              ->orWhere('patient_history.category', 'LIKE', "%{$search}%")
              ->orWhere('patient_history.uuid', 'LIKE', "%{$search}%")
              ->orWhere('patient_history.date_issued', 'LIKE', "%{$search}%")
              ->orWhere('sectors.sector', 'LIKE', "%{$search}%");
        }
    });

    $results = $query
        ->orderByRaw("
            CASE
                WHEN CONCAT_WS(' ', patient_list.lastname, patient_list.firstname, patient_list.middlename, patient_list.suffix) = ? THEN 1
                WHEN patient_history.gl_no = ? AND YEAR(patient_history.date_issued) = ? THEN 2
                WHEN CONCAT_WS(' ', patient_list.lastname, patient_list.firstname, patient_list.middlename, patient_list.suffix) LIKE ? THEN 3
                WHEN patient_history.category = ? THEN 4
                WHEN patient_history.uuid = ? THEN 5
                WHEN patient_history.date_issued    LIKE ? THEN 6
                WHEN sectors.sector LIKE ? THEN 7
                ELSE 8
            END
        ", [$searchNoComma, $search, $currentYear, "%{$searchNoComma}%", $search, $search, "%{$search}%", "%{$search}%"])
        ->orderBy('patient_list.lastname')
        ->orderBy('patient_list.firstname')
        ->orderBy('patient_history.gl_no', 'desc')
        ->get();

    return $this->attachSectorIds($results);
}

    public function getPatientDetails($identifier)
    {
        // Try to determine if identifier is UUID or gl_no
        // UUID format: MAMS-YYYY-MM-DD-NNNN
        $isUuid = strpos($identifier, 'MAMS-') === 0;

        $query = DB::table('patient_history')
            ->join('patient_list', 'patient_list.patient_id', '=', 'patient_history.patient_id')
            ->leftJoin('client_name', 'client_name.uuid', '=', 'patient_history.uuid');

        if ($isUuid) {
            $query->where('patient_history.uuid', $identifier);
        } else {
            $query->where('patient_history.gl_no', $identifier);
        }

        $row = $query->select(
            // History info
            'patient_history.uuid',
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
            'patient_list.phone_number',

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

        if ($row) {
            $row->sector_ids = DB::table('user_sectors')
                ->where('patient_id', $row->patient_id)
                ->pluck('sector_id')
                ->toArray();
        }

        return response()->json($row);
    }

    public function getPatientHistory($identifier)
    {
        // Try to determine if identifier is UUID or gl_no
        $isUuid = strpos($identifier, 'MAMS-') === 0;

        $query = DB::table('patient_history');

        if ($isUuid) {
            $query->where('uuid', $identifier);
        } else {
            $query->where('gl_no', $identifier);
        }

        // 1. Get the current record
        $current = $query->select('patient_id', 'date_issued')->first();

        if (!$current) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        // 2. Get eligibility cooldown from settings
        $cooldownDays = $this->getEligibilityCooldownDays();

        // 3. Compute eligibility date (cooldown days after date_issued)
        $eligibilityDate = Carbon::parse($current->date_issued)
            ->addDays($cooldownDays);

        // 4. Get ALL history for this patient via patient_id
        $history = DB::table('patient_history')
            ->where('patient_id', $current->patient_id)
            ->select(
                'uuid',
                'gl_no',
                'category',
                'date_issued',
                'issued_by',
                'issued_amount'
            )
            ->orderBy('date_issued', 'desc')
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

            $identifier = $request->input('identifier'); // Can be UUID or gl_no
            $isUuid = strpos($identifier, 'MAMS-') === 0;

            // Normalize and validate phone number
            $phoneNumber = $this->normalizePhoneNumber($request->input('phone_number'));

            if ($request->filled('phone_number') && $phoneNumber === null) {
                return response()->json([
                    'error' => 'Invalid phone number format. Must be 11 digits starting with 09 or +63'
                ], 422);
            }

            // Get the history record (transaction)
            if ($isUuid) {
                $history = PatientHistory::where('uuid', $identifier)->firstOrFail();
            } else {
                $history = PatientHistory::where('gl_no', $identifier)->firstOrFail();
            }

            // CASE 1: Update ONLY transaction details (don't touch patient_list)
            if ($request->has('update_transaction_only') && $request->input('update_transaction_only') == '1') {
                // Normalize hospital bill
                $hospitalBillInput = $request->input('hospital_bill');
                $hospitalBill = (is_null($hospitalBillInput) || $hospitalBillInput === '' ||
                    strtolower($hospitalBillInput) === 'null' ||
                    strtolower($hospitalBillInput) === 'n/a')
                    ? null
                    : (float) $hospitalBillInput;

                // Prepare update data
                $updateData = [
                    'category'      => $request->input('category'),
                    'partner'       => $nullify($request->input('partner')),
                    'hospital_bill' => $hospitalBill,
                    'issued_amount' => $nullify($request->input('issued_amount')),
                ];

                // If issued_by is provided (admin edit), include it
                if ($request->has('issued_by')) {
                    $updateData['issued_by'] = $nullify($request->input('issued_by'));
                }

                // If date_issued is provided (admin edit), include it
                if ($request->has('date_issued')) {
                    $updateData['date_issued'] = $nullify($request->input('date_issued'));
                }

                // Update only history-specific details
                $history->update($updateData);

                // Handle CLIENT (per-history record)
                $isChecked = $request->boolean('is_checked');

                if (!$isChecked) {
                    ClientName::updateOrCreate(
                        ['uuid' => $history->uuid], // Changed from gl_no
                        [
                            'lastname'     => $request->input('client_lastname'),
                            'firstname'    => $request->input('client_firstname'),
                            'middlename'   => $nullify($request->input('client_middlename')),
                            'suffix'       => $nullify($request->input('client_suffix')),
                            'relationship' => $nullify($request->input('relationship')),
                        ]
                    );
                } else {
                    ClientName::where('uuid', $history->uuid)->delete();
                }

                // Sync sectors if provided
                if ($request->filled('sector_ids')) {
                    $this->syncPatientSectors($history->patient_id, $request->input('sector_ids'));
                }

                return response()->json(['success' => true]);
            }

            // CASE 2: Create new patient
            if ($request->has('force_new_patient') && $request->input('force_new_patient') == '1') {
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
                    'phone_number'  => $phoneNumber,
                ]);

                // Update this history record to use the new patient_id
                $history->patient_id = $newPatient->patient_id;
            }
            // CASE 3: Use existing patient
            elseif ($request->has('use_existing_patient_id')) {
                $history->patient_id = $request->input('use_existing_patient_id');
            }
            // CASE 4: Update existing patient (affects all records with this patient_id)
            else {
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
                    'phone_number'  => $phoneNumber,
                ]);
            }

            // For cases 2, 3, 4: Update transaction details too
            $hospitalBillInput = $request->input('hospital_bill');
            $hospitalBill = (is_null($hospitalBillInput) || $hospitalBillInput === '' ||
                strtolower($hospitalBillInput) === 'null' ||
                strtolower($hospitalBillInput) === 'n/a')
                ? null
                : (float) $hospitalBillInput;

            // Prepare update data
            $updateData = [
                'category'      => $request->input('category'),
                'partner'       => $nullify($request->input('partner')),
                'hospital_bill' => $hospitalBill,
                'issued_amount' => $nullify($request->input('issued_amount')),
                'issued_by'     => $nullify($request->input('issued_by')),
            ];

            // If date_issued is provided (admin edit), include it
            if ($request->has('date_issued')) {
                $updateData['date_issued'] = $nullify($request->input('date_issued'));
            }

            // Update history-specific details
            $history->update($updateData);

            // Handle CLIENT (per-history record)
            $isChecked = $request->boolean('is_checked');

            if (!$isChecked) {
                ClientName::updateOrCreate(
                    ['uuid' => $history->uuid], // Changed from gl_no
                    [
                        'lastname'     => $request->input('client_lastname'),
                        'firstname'    => $request->input('client_firstname'),
                        'middlename'   => $nullify($request->input('client_middlename')),
                        'suffix'       => $nullify($request->input('client_suffix')),
                        'relationship' => $nullify($request->input('relationship')),
                    ]
                );
            } else {
                ClientName::where('uuid', $history->uuid)->delete();
            }

            // Sync sectors if provided
            if ($request->filled('sector_ids')) {
                $currentPatientId = $history->patient_id;
                $this->syncPatientSectors($currentPatientId, $request->input('sector_ids'));
            }

            return response()->json(['success' => true]);
        });
    }

    public function updatePatientName(Request $request)
    {
        $nullify = fn($value) => ($value === '' || $value === 'null' || strtolower($value) === 'n/a') ? null : $value;

        $phoneNumber = $this->normalizePhoneNumber($request->input('phone_number'));

        if ($request->filled('phone_number') && $phoneNumber === null) {
            return response()->json([
                'error' => 'Invalid phone number format. Must be 11 digits starting with 09 or +63'
            ], 422);
        }

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
            'phone_number'  => $phoneNumber,
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteLetter($identifier)
    {
        // Try to determine if identifier is UUID or gl_no
        $isUuid = strpos($identifier, 'MAMS-') === 0;

        if ($isUuid) {
            $patient = PatientHistory::where('uuid', $identifier)->firstOrFail()->delete();
        } else {
            $patient = PatientHistory::where('gl_no', $identifier)->firstOrFail()->delete();
        }

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

        // Get the most recent record for this patient
        $latestRecord = DB::table('patient_history')
            ->where('patient_id', $patient->patient_id)
            ->orderBy('date_issued', 'desc')
            ->first();

        if (!$latestRecord) {
            return response()->json(['eligible' => true]);
        }

        // Get eligibility cooldown from settings
        $cooldownDays = $this->getEligibilityCooldownDays();

        // Calculate eligibility date (cooldown days after last date_issued)
        // Use startOfDay to ignore time component
        $eligibilityDate = Carbon::parse($latestRecord->date_issued)
            ->startOfDay()
            ->addDays($cooldownDays);

        $today = Carbon::today()->startOfDay();

        // Check if eligible
        if ($today->greaterThanOrEqualTo($eligibilityDate)) {
            return response()->json(['eligible' => true]);
        }

        // Not eligible yet - calculate days remaining
        $daysRemaining = $today->diffInDays($eligibilityDate);

        return response()->json([
            'eligible' => false,
            'uuid' => $latestRecord->uuid, // Added UUID
            'last_gl_no' => $latestRecord->gl_no,
            'last_issued_at' => $latestRecord->date_issued,
            'eligibility_date' => $eligibilityDate->toDateString(),
            'days_remaining' => $daysRemaining
        ]);
    }

    public function checkEligibilityById(Request $request)
    {
        $patientId = $request->input('patient_id');

        if (!$patientId) {
            return response()->json(['eligible' => true]);
        }

        // Get the most recent record for this patient
        $latestRecord = DB::table('patient_history')
            ->where('patient_id', $patientId)
            ->orderBy('date_issued', 'desc')
            ->first();

        if (!$latestRecord) {
            return response()->json(['eligible' => true]);
        }

        // Get eligibility cooldown from settings
        $cooldownDays = $this->getEligibilityCooldownDays();

        // Calculate eligibility date (cooldown days after last date_issued)
        // Use startOfDay to ignore time component
        $eligibilityDate = Carbon::parse($latestRecord->date_issued)
            ->startOfDay()
            ->addDays($cooldownDays);

        $today = Carbon::today()->startOfDay();

        // Check if eligible
        if ($today->greaterThanOrEqualTo($eligibilityDate)) {
            return response()->json(['eligible' => true]);
        }

        // Not eligible yet - calculate days remaining
        $daysRemaining = $today->diffInDays($eligibilityDate);

        return response()->json([
            'eligible' => false,
            'uuid' => $latestRecord->uuid, // Added UUID
            'last_gl_no' => $latestRecord->gl_no,
            'last_issued_at' => $latestRecord->date_issued,
            'eligibility_date' => $eligibilityDate->toDateString(),
            'days_remaining' => $daysRemaining
        ]);
    }

    public function getAllPatientsWithEligibility()
    {
        // Get eligibility cooldown from settings
        $cooldownDays = $this->getEligibilityCooldownDays();

        // Get all unique patients with their most recent record
        $patients = DB::table('patient_list')
            ->leftJoin('patient_history', function ($join) {
                $join->on('patient_list.patient_id', '=', 'patient_history.patient_id')
                    ->whereRaw('patient_history.date_issued = (
                        SELECT MAX(ph2.date_issued) 
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
                'patient_list.phone_number',
                'patient_history.uuid',
                'patient_history.gl_no',
                'patient_history.category',
                'patient_history.date_issued as last_issued_at'
            )
            ->get();

        $today = Carbon::today()->startOfDay();

        // Pre-fetch all sector mappings in one query to avoid N+1
        $allSectorMappings = DB::table('user_sectors')
            ->select('patient_id', 'sector_id')
            ->get()
            ->groupBy('patient_id');

        $patientsWithEligibility = $patients->map(function ($patient) use ($today, $cooldownDays, $allSectorMappings) {
            $sectorIds = isset($allSectorMappings[$patient->patient_id])
                ? $allSectorMappings[$patient->patient_id]->pluck('sector_id')->toArray()
                : [];

            if (!$patient->last_issued_at) {
                return array_merge((array)$patient, [
                    'eligible'         => true,
                    'eligibility_date' => null,
                    'sector_ids'       => $sectorIds,
                ]);
            }

            $eligibilityDate = Carbon::parse($patient->last_issued_at)
                ->startOfDay()
                ->addDays($cooldownDays);

            $eligible = $today->greaterThanOrEqualTo($eligibilityDate);
            $daysRemaining = max(0, $today->diffInDays($eligibilityDate));

            return array_merge((array)$patient, [
                'eligible'         => $eligible,
                'eligibility_date' => $eligibilityDate->toDateString(),
                'days_remaining'   => $eligible ? null : $daysRemaining,
                'sector_ids'       => $sectorIds,
            ]);
        });

        return response()->json($patientsWithEligibility);
    }
}
