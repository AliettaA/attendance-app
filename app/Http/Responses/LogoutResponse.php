<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        $role = $request->input('logout_role');

        if ($role === 'admin') {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }
}
