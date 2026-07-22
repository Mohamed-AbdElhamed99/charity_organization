<?php

namespace App\Support;

use App\Enums\BeneficiaryType;
use App\Models\Beneficiary;
use App\Models\User;

class BeneficiarySensitiveFields
{
    /** @var list<string> */
    public const BASE_FIELDS = [
        'notes',
    ];

    /** @var list<string> */
    public const INDIVIDUAL_FIELDS = [
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'birthdate',
        'national_id',
        'phone',
        'address',
        'country_id',
        'state_id',
        'health_status',
        'education_level',
        'marital_status',
        'employment_status',
        'monthly_income',
        'date_of_father_death',
        'school_year',
        'sibling_number',
        'behavior_notes',
        'notes',
    ];

    /** @var list<string> */
    public const FAMILY_FIELDS = [
        'household_name',
        'national_id',
        'phone',
        'address',
        'village',
        'country_id',
        'state_id',
        'social_status',
        'total_members',
        'monthly_income',
        'housing_type',
        'housing_ownership',
        'monthly_rent',
        'notes',
    ];

    /** @var list<string> */
    public const FAMILY_MEMBER_FIELDS = [
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'birthdate',
        'national_id',
        'relation',
        'health_status',
        'education_level',
        'marital_status',
        'employment_status',
        'monthly_income',
        'date_of_father_death',
        'school_year',
        'sibling_number',
        'behavior_notes',
    ];

    /** @var list<string> */
    public const ORGANIZATION_FIELDS = [
        'name',
        'organization_type',
        'charity_number',
        'phone',
        'email',
        'address',
        'country_id',
        'state_id',
        'contact_person',
        'contact_phone',
        'notes',
    ];

    /** @var list<string> */
    public const ASSESSMENT_FIELDS = [
        'purpose',
        'housing_details',
        'economic_details',
        'health_details',
        'family_details',
        'researcher_opinion',
        'recommended_aid_amount',
        'rejection_reason',
    ];

    public static function userCanViewField(User $user, Beneficiary $beneficiary, string $field): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if (! $user->can('view_sensitive_details_beneficiaries')) {
            return false;
        }

        return $beneficiary->userCanAccessField($user, $field);
    }

    public static function userCanViewSensitiveDetails(User $user, Beneficiary $beneficiary): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if (! $user->can('view_sensitive_details_beneficiaries')) {
            return false;
        }

        $grant = $beneficiary->userAccess()
            ->where('user_id', $user->id)
            ->first();

        if (! $grant) {
            return false;
        }

        if ($grant->expires_in_seconds !== null) {
            $expiresAt = $grant->granted_at->addSeconds($grant->expires_in_seconds);

            if (now()->isAfter($expiresAt)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public static function fieldsForType(BeneficiaryType $type): array
    {
        return match ($type) {
            BeneficiaryType::Individual => self::INDIVIDUAL_FIELDS,
            BeneficiaryType::Family => self::FAMILY_FIELDS,
            BeneficiaryType::Organization => self::ORGANIZATION_FIELDS,
        };
    }

    public static function mask(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return '••••••';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function filterProfileData(
        User $user,
        Beneficiary $beneficiary,
        array $data,
        string $prefix = '',
    ): array {
        foreach ($data as $key => $value) {
            $fieldKey = $prefix === '' ? $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                if ($key === 'members') {
                    $data[$key] = array_map(
                        fn (array $member) => self::filterMemberData($user, $beneficiary, $member),
                        $value,
                    );

                    continue;
                }

                $data[$key] = self::filterProfileData($user, $beneficiary, $value, $fieldKey);

                continue;
            }

            if (! self::userCanViewField($user, $beneficiary, (string) $key)) {
                $data[$key] = self::mask($value);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $member
     * @return array<string, mixed>
     */
    public static function filterMemberData(User $user, Beneficiary $beneficiary, array $member): array
    {
        foreach ($member as $key => $value) {
            if ($key === 'id' || $key === 'subtype') {
                continue;
            }

            if (! self::userCanViewField($user, $beneficiary, (string) $key)) {
                $member[$key] = self::mask($value);
            }
        }

        return $member;
    }
}
