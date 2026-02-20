<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function getLogs(Request $request)
    {
        $query = ActivityLog::query();

        // Filter by action
        if ($request->has('action') && $request->input('action') !== 'ALL') {
            $query->where('action', $request->input('action'));
        }

        // Search across columns
        if ($request->has('search') && $request->input('search') !== '') {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('performed_by', 'LIKE', "%{$search}%")
                  ->orWhere('action', 'LIKE', "%{$search}%")
                  ->orWhere('target', 'LIKE', "%{$search}%")
                  ->orWhere('changes', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['logs' => $logs]);
    }

    public function getActionTypes()
    {
        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');
        return response()->json(['actions' => $actions]);
    }
}