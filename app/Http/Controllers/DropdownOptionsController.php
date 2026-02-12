<?php

namespace App\Http\Controllers;

use App\Models\Preferences;
use App\Models\Partners;
use App\Models\Sectors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DropdownOptionsController extends Controller
{
    // ===================== GET ALL OPTIONS =====================
    
    public function getPreferenceOptions()
    {
        try {
            $options = Preferences::orderBy('preference')->get();
            return response()->json($options);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch preference options'], 500);
        }
    }

    public function getPartnerOptions()
    {
        try {
            $options = Partners::orderBy('category')->orderBy('partner')->get();
            return response()->json($options);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch partner options'], 500);
        }
    }

    public function getSectorOptions()
    {
        try {
            $options = Sectors::orderBy('sector')->get();
            return response()->json($options);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch sector options'], 500);
        }
    }

    public function getAllOptions()
    {
        try {
            $preferences = Preferences::orderBy('preference')->get();
            $partners = Partners::orderBy('category')->orderBy('partner')->get();
            $sectors = Sectors::orderBy('sector')->get();

            return response()->json([
                'preferences' => $preferences,
                'partners' => $partners,
                'sectors' => $sectors
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch options'], 500);
        }
    }

    // ===================== ADD OPTIONS =====================

    public function addPreferenceOption(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string|max:255|unique:preferences,preference'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $option = Preferences::create([
                'preference' => trim($request->value)
            ]);

            return response()->json([
                'message' => 'Preference option added successfully',
                'option' => $option
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add preference option'], 500);
        }
    }

    public function addPartnerOption(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|in:MEDICINE,LABORATORY,HOSPITAL',
            'value' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Check for duplicate within the same category
        $exists = Partners::where('category', $request->category)
            ->where('partner', trim($request->value))
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'This partner already exists in the selected category'], 422);
        }

        try {
            $option = Partners::create([
                'category' => $request->category,
                'partner' => trim($request->value)
            ]);

            return response()->json([
                'message' => 'Partner option added successfully',
                'option' => $option
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add partner option'], 500);
        }
    }

    public function addSectorOption(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string|max:255|unique:sectors,sector'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $option = Sectors::create([
                'sector' => trim($request->value)
            ]);

            return response()->json([
                'message' => 'Sector option added successfully',
                'option' => $option
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add sector option'], 500);
        }
    }

    // ===================== DELETE OPTIONS =====================

    public function deletePreferenceOption($id)
    {
        try {
            $option = Preferences::findOrFail($id);
            $option->delete();

            return response()->json([
                'message' => 'Preference option deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Option not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete preference option'], 500);
        }
    }

    public function deletePartnerOption($id)
    {
        try {
            $option = Partners::findOrFail($id);
            $option->delete();

            return response()->json([
                'message' => 'Partner option deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Option not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete partner option'], 500);
        }
    }

    public function deleteSectorOption($id)
    {
        try {
            $option = Sectors::findOrFail($id);
            $option->delete();

            return response()->json([
                'message' => 'Sector option deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Option not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete sector option'], 500);
        }
    }
}