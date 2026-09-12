<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use Illuminate\Http\Request;

class AdminActionLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminActionLog::with(['admin', 'batch', 'reservation'])->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(15)->withQueryString();

        return view('admin.action-logs.index', compact('logs'));
    }
}
