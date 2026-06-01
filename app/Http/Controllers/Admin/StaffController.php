<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        $staffMembers = User::where('role', 'user')
            ->orderBy('name')
            ->get();

        return view('admin.staff.list', compact('staffMembers'));
    }
}
