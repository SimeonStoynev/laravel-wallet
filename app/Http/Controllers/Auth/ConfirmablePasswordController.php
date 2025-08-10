<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConfirmablePasswordController
{
    public function show(): Response
    {
        return response()->noContent();
    }

    public function store(Request $request): Response
    {
        return response()->noContent();
    }
}
