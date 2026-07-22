<?php

namespace App\Support;

use App\Enums\ModulePermission;
use App\Models\User;

final class AuthenticatedHome
{
    public static function url(User $user): string
    {
        return $user->can(ModulePermission::SYSTEM->permission('access_dashboard'))
            ? '/admin'
            : route('account.profile.edit');
    }
}
