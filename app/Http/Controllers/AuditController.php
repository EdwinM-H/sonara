<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with('user')->latest()->paginate(25);

        return view('admin.audit.index', compact('logs'));
    }
}