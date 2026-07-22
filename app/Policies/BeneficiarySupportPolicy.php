<?php

namespace App\Policies;

use App\Models\BeneficiarySupport;
use App\Models\User;

class BeneficiarySupportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_beneficiary_support_reports');
    }

    public function view(User $user, BeneficiarySupport $support): bool
    {
        return $user->can('view_beneficiary_support_reports');
    }

    public function create(User $user): bool
    {
        return $user->can('create_beneficiary_supports');
    }

    public function update(User $user, BeneficiarySupport $support): bool
    {
        return $user->can('create_beneficiary_supports');
    }
}
