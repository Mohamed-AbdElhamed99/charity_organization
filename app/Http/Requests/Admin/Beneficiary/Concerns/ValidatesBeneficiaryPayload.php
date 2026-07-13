<?php

namespace App\Http\Requests\Admin\Beneficiary\Concerns;

use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use App\Enums\IndividualSubtype;
use App\Enums\UserGender;
use App\Models\Beneficiary;
use Illuminate\Validation\Rule;

trait ValidatesBeneficiaryPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function baseRules(bool $isUpdate = false): array
    {
        $rules = [
            'notes' => ['nullable', 'string'],
        ];

        if (! $isUpdate) {
            $rules['type'] = ['required', 'string', Rule::enum(BeneficiaryType::class)];
            $rules['code'] = ['nullable', 'string', 'max:255', Rule::unique('beneficiaries', 'code')];
            $rules['status'] = ['nullable', 'string', Rule::enum(BeneficiaryStatus::class)];
        } else {
            $rules['type'] = ['prohibited'];
            $rules['status'] = ['sometimes', 'string', Rule::enum(BeneficiaryStatus::class)];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    protected function individualRules(string $prefix = 'individual'): array
    {
        $isChild = fn () => $this->input("{$prefix}.subtype") === IndividualSubtype::Child->value;

        return [
            "{$prefix}" => ['required', 'array'],
            "{$prefix}.subtype" => ['required', 'string', Rule::enum(IndividualSubtype::class)],
            "{$prefix}.first_name" => ['required', 'string', 'max:255'],
            "{$prefix}.middle_name" => ['nullable', 'string', 'max:255'],
            "{$prefix}.last_name" => ['required', 'string', 'max:255'],
            "{$prefix}.gender" => ['nullable', 'string', Rule::enum(UserGender::class)],
            "{$prefix}.birthdate" => ['nullable', 'date'],
            "{$prefix}.national_id" => ['nullable', 'string', 'max:255'],
            "{$prefix}.phone" => ['nullable', 'string', 'max:255'],
            "{$prefix}.address" => ['nullable', 'string', 'max:255'],
            "{$prefix}.country_id" => ['nullable', 'integer', Rule::exists('countries', 'id')],
            "{$prefix}.state_id" => ['nullable', 'integer', Rule::exists('states', 'id')],
            "{$prefix}.health_status" => ['nullable', 'string', 'max:255'],
            "{$prefix}.education_level" => ['nullable', 'string', 'max:255'],
            "{$prefix}.marital_status" => ['nullable', 'string', 'max:255'],
            "{$prefix}.employment_status" => ['nullable', 'string', 'max:255'],
            "{$prefix}.monthly_income" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}.date_of_father_death" => [Rule::requiredIf($isChild), 'nullable', 'date'],
            "{$prefix}.school_year" => [Rule::requiredIf($isChild), 'nullable', 'string', 'max:255'],
            "{$prefix}.sibling_number" => ['nullable', 'integer', 'min:0', 'max:255'],
            "{$prefix}.behavior_notes" => ['nullable', 'string'],
            "{$prefix}.notes" => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function familyRules(string $prefix = 'family'): array
    {
        return array_merge([
            "{$prefix}" => ['required', 'array'],
            "{$prefix}.household_name" => ['required', 'string', 'max:255'],
            "{$prefix}.national_id" => ['nullable', 'string', 'max:255'],
            "{$prefix}.phone" => ['nullable', 'string', 'max:255'],
            "{$prefix}.address" => ['nullable', 'string', 'max:255'],
            "{$prefix}.village" => ['nullable', 'string', 'max:255'],
            "{$prefix}.country_id" => ['nullable', 'integer', Rule::exists('countries', 'id')],
            "{$prefix}.state_id" => ['nullable', 'integer', Rule::exists('states', 'id')],
            "{$prefix}.social_status" => ['nullable', 'string', 'max:255'],
            "{$prefix}.total_members" => ['nullable', 'integer', 'min:1'],
            "{$prefix}.monthly_income" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}.housing_type" => ['nullable', 'string', 'max:255'],
            "{$prefix}.housing_ownership" => ['nullable', 'string', 'max:255'],
            "{$prefix}.monthly_rent" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}.notes" => ['nullable', 'string'],
            "{$prefix}.members" => ['required', 'array', 'min:1'],
        ], $this->familyMemberRules("{$prefix}.members.*"));
    }

    /**
     * @return array<string, mixed>
     */
    protected function familyMemberRules(string $prefix): array
    {
        $isChild = fn () => $this->input(str_replace('.*', '.0', $prefix).'.subtype') === IndividualSubtype::Child->value;

        return [
            "{$prefix}.id" => ['nullable', 'integer', Rule::exists('beneficiary_family_members', 'id')],
            "{$prefix}.subtype" => ['required', 'string', Rule::enum(IndividualSubtype::class)],
            "{$prefix}.first_name" => ['required', 'string', 'max:255'],
            "{$prefix}.middle_name" => ['nullable', 'string', 'max:255'],
            "{$prefix}.last_name" => ['nullable', 'string', 'max:255'],
            "{$prefix}.gender" => ['nullable', 'string', Rule::enum(UserGender::class)],
            "{$prefix}.birthdate" => ['nullable', 'date'],
            "{$prefix}.national_id" => ['nullable', 'string', 'max:255'],
            "{$prefix}.relation" => ['nullable', 'string', 'max:255'],
            "{$prefix}.health_status" => ['nullable', 'string', 'max:255'],
            "{$prefix}.education_level" => ['nullable', 'string', 'max:255'],
            "{$prefix}.marital_status" => ['nullable', 'string', 'max:255'],
            "{$prefix}.employment_status" => ['nullable', 'string', 'max:255'],
            "{$prefix}.monthly_income" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}.date_of_father_death" => ['nullable', 'date'],
            "{$prefix}.school_year" => ['nullable', 'string', 'max:255'],
            "{$prefix}.sibling_number" => ['nullable', 'integer', 'min:0', 'max:255'],
            "{$prefix}.behavior_notes" => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function organizationRules(string $prefix = 'organization'): array
    {
        return [
            "{$prefix}" => ['required', 'array'],
            "{$prefix}.name" => ['required', 'string', 'max:255'],
            "{$prefix}.organization_type" => ['nullable', 'string', 'max:255'],
            "{$prefix}.charity_number" => ['nullable', 'string', 'max:255'],
            "{$prefix}.phone" => ['nullable', 'string', 'max:255'],
            "{$prefix}.email" => ['nullable', 'email', 'max:255'],
            "{$prefix}.address" => ['nullable', 'string', 'max:255'],
            "{$prefix}.country_id" => ['nullable', 'integer', Rule::exists('countries', 'id')],
            "{$prefix}.state_id" => ['nullable', 'integer', Rule::exists('states', 'id')],
            "{$prefix}.contact_person" => ['nullable', 'string', 'max:255'],
            "{$prefix}.contact_phone" => ['nullable', 'string', 'max:255'],
            "{$prefix}.notes" => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesForType(?string $type, bool $isUpdate = false): array
    {
        return match ($type) {
            BeneficiaryType::Individual->value => $this->individualRules(),
            BeneficiaryType::Family->value => $this->familyRules(),
            BeneficiaryType::Organization->value => $this->organizationRules(),
            default => $isUpdate
                ? $this->rulesForExistingBeneficiaryType()
                : [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesForExistingBeneficiaryType(): array
    {
        $beneficiary = $this->route('beneficiary');

        if (! $beneficiary instanceof Beneficiary) {
            return [];
        }

        return match ($beneficiary->type) {
            BeneficiaryType::Individual => $this->individualRules(),
            BeneficiaryType::Family => $this->familyRules(),
            BeneficiaryType::Organization => $this->organizationRules(),
        };
    }
}
