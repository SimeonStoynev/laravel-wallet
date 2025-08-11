<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Contracts\View\View as ViewContract;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): ViewContract
    {
        /** @var view-string $view */
        // Serve the React SPA shell; the frontend handles the /login route
        $view = 'react';
        return view($view);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // If this is an AJAX/JSON request, respond with JSON to avoid 3xx follow-ups in XHR
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Logged in successfully',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
