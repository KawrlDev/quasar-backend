<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Auth\AuthController;

// Wrap auth routes in web middleware for sessions
Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Protect the user endpoint - require authentication
    Route::middleware('auth')->get('/user', [AuthController::class, 'user']);
});

// PatientController
Route::post('/patients', [PatientController::class, 'addPatient']);
Route::post('/patients/existing', [PatientController::class, 'existingPatientList']);
Route::post('/patients/check-eligibility', [PatientController::class, 'checkEligibility']);
Route::post('/patients/check-eligibility-by-id', [PatientController::class, 'checkEligibilityById']);
Route::get('/patients', [PatientController::class, 'getPatients']);
Route::get('/patients/all-with-eligibility', [PatientController::class, 'getAllPatientsWithEligibility']);
Route::get('/patients/search', [PatientController::class, 'search']);
Route::get('/patient-records', [PatientController::class, 'filterByDate']);
Route::get('/patient-details/{glNum}', [PatientController::class, 'getPatientDetails']);
Route::get('/patient-history/{glNum}', [PatientController::class, 'getPatientHistory']);
Route::post('/patient-details/update', [PatientController::class, 'updatePatientDetails']);
Route::post('/patient-details/update-name', [PatientController::class, 'updatePatientName']);
Route::post('/patient-name/update', [PatientController::class, 'updatePatientName']);
Route::delete('/patient-details/delete/{glNum}', [PatientController::class, 'deleteLetter']);

//BudgetController
Route::post('/create-yearly-budget', [BudgetController::class, 'createYearlyBudget']);
Route::post('/add-supplementary-bonus', [BudgetController::class, 'addSupplementaryBonus']);
Route::get('/yearly-budget', [BudgetController::class, 'getYearlyBudget']);
Route::get('/supplementary-bonus', [BudgetController::class, 'getSupplementaryBonus']);
Route::get('/issued-amounts-by-year', [BudgetController::class, 'getIssuedAmountByYear']);

//DashboardController
Route::get('/total-patients-and-amount', [DashboardController::class, 'getTotalPatientsAndAmountReleased']);
Route::get('/category-cards', [DashboardController::class, 'getCategoryData']);
Route::get('/amount-given', [DashboardController::class, 'getAmountGiven']);
Route::get('/monthly-patients', [DashboardController::class, 'getMonthlyPatients']);
Route::get('/barangay-records', [DashboardController::class, 'getBarangayData']);

//SettingsController
Route::get('/get-eligibility-cooldown', [SettingsController::class, 'getEligibilityCooldown']);
Route::post('/update-eligibility-cooldown', [SettingsController::class, 'updateEligibilityCooldown']);
Route::get('/accounts', [SettingsController::class, 'getAccounts']);
Route::post('/new-account', [SettingsController::class, 'createAccount']);
Route::post('/delete-account', [SettingsController::class, 'deleteAccount']);
