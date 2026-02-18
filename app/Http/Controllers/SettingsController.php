<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\WebsiteSettings;
use App\Models\User;
use App\Models\ActivityLog;

class SettingsController extends Controller
{
    private function logActivity(Request $request, string $action, string $changes = '', string $target = 'SETTINGS'): void
    {
        ActivityLog::create([
            'performed_by' => $request->input('performed_by') ?? 'Unknown',
            'action'       => $action,
            'target'       => $target,
            'changes'      => $changes,
        ]);
    }
    public function getEligibilityCooldown()
    {
        $eligibilityCooldown = WebsiteSettings::where('id', 1)->value('eligibility_cooldown');
        return response()->json([
            'days' => $eligibilityCooldown ?? 90
        ]);
    }

    public function updateEligibilityCooldown(Request $request)
    {
        $old = WebsiteSettings::where('id', 1)->value('eligibility_cooldown') ?? 90;
        $new = $request->input('days');
        WebsiteSettings::where('id', 1)->update(['eligibility_cooldown' => $new]);

        $this->logActivity($request, 'ELIGIBILITY UPDATED', "Cooldown: '{$old} days' → '{$new} days'", 'ELIGIBILITY COOLDOWN');


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
            'ROLE'     => $request->input('role')
        ]);

        $this->logActivity($request, 'ACCOUNT CREATED', "Username: '{$account->USERNAME}' | Role: '{$account->ROLE}'", 'ACCOUNT OPTIONS');


        return response()->json(['success' => true]);
    }
    public function updateAccount(Request $request)
    {
        $userId = $request->input('id');
        $existing = User::where('ID', $userId)->first();

        $updateData = [
            'USERNAME' => $request->input('username'),
            'ROLE'     => $request->input('role')
        ];

        $changes = "Username: '{$existing->USERNAME}' → '{$updateData['USERNAME']}' | Role: '{$existing->ROLE}' → '{$updateData['ROLE']}'";

        if ($request->has('password') && $request->input('password') !== null && $request->input('password') !== '') {
            $updateData['PASSWORD'] = Hash::make($request->input('password'));
            $changes .= ' | Password: changed';
        }

        User::where('ID', $userId)->update($updateData);
        $this->invalidateUserSessions($userId);

        $this->logActivity($request, 'ACCOUNT UPDATED', $changes, 'ACCOUNT OPTIONS');
        return response()->json(['success' => true]);
    }


    public function deleteAccount(Request $request)
    {
        $userId = $request->input('id');
        $existing = User::where('ID', $userId)->first();

        $this->invalidateUserSessions($userId);
        User::where('ID', '=', $userId)->delete();

     $this->logActivity($request, 'ACCOUNT DELETED', "Username: '{$existing->USERNAME}' | Role: '{$existing->ROLE}'", 'ACCOUNT OPTIONS');

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
