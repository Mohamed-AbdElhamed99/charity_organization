<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\User;
use App\Support\BeneficiarySensitiveFields;

class BeneficiaryIdentityVisibilityResolver
{
    public function canViewIdentity(User $user, Beneficiary $beneficiary): bool
    {
        return BeneficiarySensitiveFields::userCanViewSensitiveDetails($user, $beneficiary);
    }

    public function displayIdentity(User $user, Beneficiary $beneficiary): string
    {
        if ($this->canViewIdentity($user, $beneficiary)) {
            return $beneficiary->display_name;
        }

        return $beneficiary->code;
    }
}
