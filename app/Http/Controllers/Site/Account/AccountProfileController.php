<?php

namespace App\Http\Controllers\Site\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\Account\UpdateAccountProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AccountProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        [$firstName, $lastName] = $this->splitName($user->name);

        return Inertia::render('site/account/profile', [
            'profile' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified' => $user->email_verified_at !== null,
            ],
            'status' => session('status'),
        ]);
    }

    public function update(UpdateAccountProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $emailChanged = $validated['email'] !== $user->email;

        $user->fill([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->password_set_at = now();
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return back()->with('status', __('We sent a new verification link to your updated email.'));
        }

        return back()->with('status', __('Profile updated.'));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = explode(' ', trim($name), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
