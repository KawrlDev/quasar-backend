<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
    
    public function updateAccount(Request $request)
    {
        $userId = $request->input('id');
        
        $updateData = [
            'USERNAME' => $request->input('username'),
            'ROLE' => $request->input('role')
        ];

        // Only update password if provided
        if ($request->has('password') && $request->input('password') !== null && $request->input('password') !== '') {
            $updateData['PASSWORD'] = Hash::make($request->input('password'));
        }

        $account = User::where('ID', $userId)->update($updateData);
        
        // Invalidate all sessions for this user
        $this->invalidateUserSessions($userId);
        
        return response()->json(['success' => true]);
    }
    
    public function deleteAccount(Request $request)
    {
        $userId = $request->input('id');
        
        // Invalidate all sessions for this user before deleting
        $this->invalidateUserSessions($userId);
        
        $account = User::where('ID', '=', $userId)->delete();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Invalidate all sessions for a specific user
     */
    private function invalidateUserSessions($userId)
    {
        // Delete all sessions for this user from the sessions table
        // This assumes you're using database sessions
        DB::table('sessions')
            ->where('user_id', $userId)
            ->delete();
    }
}