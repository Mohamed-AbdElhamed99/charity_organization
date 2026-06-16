<?php

namespace App\Services;

use App\Enums\DonorType;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DonorIdentityService
{
    public function firstOrCreateByEmail(
        string $firstName,
        string $lastName,
        string $email,
        ?string $phone = null,
        ?int $countryId = null,
    ): User {
        $name = trim($firstName.' '.$lastName);

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'country_id' => $countryId,
                'password' => Hash::make(Str::random(32)),
            ]);
        } else {
            $user->fill([
                'name' => $name,
                'phone' => $phone ?? $user->phone,
                'country_id' => $countryId ?? $user->country_id,
            ]);
            $user->save();
        }

        if (! $user->hasRole('donor')) {
            $user->assignRole('donor');
        }

        DonorProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['type' => DonorType::Individual],
        );

        return $user;
    }
}
