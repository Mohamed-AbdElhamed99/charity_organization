<?php

namespace App\Http\Controllers\Site\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\Auth\LoginDonorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedDonorSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('site/account/login', [
            'status' => session('status'),
        ]);
    }

    public function store(LoginDonorRequest $request): RedirectResponse
    {
        $request->authenticate();

        return redirect()->intended(route('account.donations.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
