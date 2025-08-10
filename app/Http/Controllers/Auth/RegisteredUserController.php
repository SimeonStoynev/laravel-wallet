<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return response()->noContent();
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            // IMPORTANT: store hashed password so Auth can verify credentials
            'password' => Hash::make($validated['password']),
            'role' => 'merchant',
            'amount' => 0,
        ]);

        // Simulate sending a registration email by logging it
        Log::info('Registration email queued (simulated) for user: '.$user->email);

        Auth::login($user);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Registered successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
