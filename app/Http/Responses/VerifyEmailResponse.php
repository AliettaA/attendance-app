<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request)
    {
        if ($request->user()->role === 'admin') {
            return redirect()->route('admin.attendance.index', ['verified' => 1]);
        }

        return redirect()->route('attendance.index', ['verified' => 1]);
    }
}
