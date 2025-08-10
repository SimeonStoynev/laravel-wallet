<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\View\View as ViewContract;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function index(Request $request): RedirectResponse|ViewContract
    {
        $user = $request->user();
        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        /** @var view-string $view */
        $view = 'auth.verify-email';
        return view($view);
    }
}
