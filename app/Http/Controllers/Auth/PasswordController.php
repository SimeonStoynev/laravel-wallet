<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PasswordController
{
    public function update(Request $request): Response
    {
        return response()->noContent();
    }
}
