<?php

namespace App\Policies;

use App\Models\Beneficiary;
use App\Models\User;
use App\Support\BeneficiarySensitiveFields;

class BeneficiaryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_beneficiaries');
    }

    public function view(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('view_beneficiaries');
    }

    public function viewSensitiveDetails(User $user, Beneficiary $beneficiary): bool
    {
        return BeneficiarySensitiveFields::userCanViewSensitiveDetails($user, $beneficiary);
    }

    public function viewField(User $user, Beneficiary $beneficiary, string $field): bool
    {
        return BeneficiarySensitiveFields::userCanViewField($user, $beneficiary, $field);
    }

    public function create(User $user): bool
    {
        return $user->can('create_beneficiaries');
    }

    public function update(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('edit_beneficiaries');
    }

    public function delete(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('delete_beneficiaries');
    }

    public function export(User $user, Beneficiary $beneficiary): bool
    {
        return $this->viewSensitiveDetails($user, $beneficiary);
    }
}
