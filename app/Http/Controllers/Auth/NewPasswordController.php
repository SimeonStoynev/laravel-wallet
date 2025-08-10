<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NewPasswordController
{
    public function create(): Response
    {
        return response()->noContent();
    }

    public function store(Request $request): Response
    {
        return response()->noContent();
    }
}
