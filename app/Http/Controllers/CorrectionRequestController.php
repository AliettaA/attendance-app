<?php

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use Illuminate\Http\Request;

class CorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        if (! in_array($status, ['pending', 'approved'], true)) {
            $status = 'pending';
        }

        $correctionRequests = CorrectionRequest::where('user_id', $request->user()->id)
            ->where('status', $status)
            ->with(['user', 'attendance'])
            ->latest()
            ->get();

        return view('correction_requests.index', compact('correctionRequests', 'status'));
    }
}
