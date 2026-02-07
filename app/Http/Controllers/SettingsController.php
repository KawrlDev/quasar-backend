<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\WebsiteSettings;
use App\Models\User;

class SettingsController extends Controller
{
    public function getEligibilityCooldown()
    {
        $eligibilityCooldown = WebsiteSettings::where('id', 1)->value('eligibility_cooldown');
        return response()->json([
            'days' => $eligibilityCooldown ?? 90
        ]);
    }
    public function updateEligibilityCooldown(Request $request)
    {
        $eligibilityCooldown = WebsiteSettings::where('id', 1)->update(['eligibility_cooldown' => $request->input('days')]);
        return response()->json(['success' => true]);
    }
    public function getAccounts()
    {
        $accounts = User::select('ID', 'USERNAME', 'PASSWORD', 'ROLE')->orderBy('ID')->get();
        return response()->json([$accounts]);
    }
    public function createAccount(Request $request)
    {
        $account = User::create([
            'USERNAME' => $request->input('username'),
            'PASSWORD' => Hash::make($request->input('password')),
            'ROLE' => $request->input('role')
        ]);
        return response()->json(['success' => true]);
    }
    public function deleteAccount(Request $request)
    {
        $account = User::where('ID', '=', $request->input('id'))->delete();
        return response()->json(['success' => true]);
    }
}
