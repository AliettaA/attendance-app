<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        $staffMembers = User::where('role', 'user')
            ->orderBy('name')
            ->get();

        return view('admin.staff.list', compact('staffMembers'));
    }
}
