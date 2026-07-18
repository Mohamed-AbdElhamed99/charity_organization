<?php

namespace App\Http\Controllers\Site\Auth;

use App\Enums\DonorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\Auth\RegisterDonorRequest;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredDonorController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('site/account/register', [
            'status' => session('status'),
        ]);
    }

    public function store(RegisterDonorRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $name = trim($validated['first_name'].' '.$validated['last_name']);

        $existing = User::query()->where('email', $validated['email'])->first();

        // An account with this email already exists from a prior guest
        // checkout (random password, never claimed): send a reset-password
        // link to activate it instead of a duplicate-email error.
        if ($existing !== null && ! $existing->has_usable_password) {
            Password::broker('users')->sendResetLink(['email' => $existing->email]);

            return back()->with('status', __('An account already exists for this email from a previous donation. We sent a link to set your password and activate it.'));
        }

        if ($existing !== null) {
            return back()->withErrors(['email' => __('This email is already registered. Please log in instead.')])->onlyInput('email');
        }

        $user = User::create([
            'name' => $name,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'password_set_at' => now(),
        ]);

        $user->assignRole('donor');

        DonorProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['type' => DonorType::Individual],
        );

        event(new Registered($user));

        Auth::guard('web')->login($user);

        return redirect()->route('account.donations.index');
    }
}
