<?php

namespace App\Http\Resources\Admin\Beneficiary;

use App\Enums\BeneficiaryType;
use App\Models\Beneficiary;
use App\Models\User;
use App\Support\BeneficiarySensitiveFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Beneficiary */
class BeneficiaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canViewSensitive = $user !== null && $user->can('viewSensitiveDetails', $this->resource);

        $data = [
            'id' => $this->id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'code' => $this->code,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'display_name' => $this->display_name,
            'primary_contact' => $this->resolvePrimaryContact($canViewSensitive, $user),
            'notes' => $canViewSensitive && ($user === null || BeneficiarySensitiveFields::userCanViewField($user, $this->resource, 'notes'))
                ? $this->notes
                : BeneficiarySensitiveFields::mask($this->notes),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'can_view_sensitive' => $canViewSensitive,
            'individual' => $this->when(
                $this->type === BeneficiaryType::Individual,
                fn () => $this->formatIndividual($request, $canViewSensitive)
            ),
            'family' => $this->when(
                $this->type === BeneficiaryType::Family,
                fn () => $this->formatFamily($request, $canViewSensitive)
            ),
            'organization' => $this->when(
                $this->type === BeneficiaryType::Organization,
                fn () => $this->formatOrganization($request, $canViewSensitive)
            ),
            'assessments' => BeneficiaryAssessmentResource::collection(
                $this->whenLoaded('assessments')
            ),
            'campaigns_count' => $this->when(isset($this->campaigns_count), $this->campaigns_count),
        ];

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatIndividual(Request $request, bool $canViewSensitive): ?array
    {
        if ($this->individual === null) {
            return null;
        }

        $payload = [
            'id' => $this->individual->id,
            'subtype' => $this->individual->subtype?->value,
            'first_name' => $this->individual->first_name,
            'middle_name' => $this->individual->middle_name,
            'last_name' => $this->individual->last_name,
            'full_name' => $this->individual->full_name,
            'gender' => $this->individual->gender?->value,
            'birthdate' => $this->individual->birthdate?->toDateString(),
            'national_id' => $this->individual->national_id,
            'phone' => $this->individual->phone,
            'address' => $this->individual->address,
            'country_id' => $this->individual->country_id,
            'state_id' => $this->individual->state_id,
            'country' => $this->individual->relationLoaded('country') && $this->individual->country
                ? ['id' => $this->individual->country->id, 'name' => $this->individual->country->name]
                : null,
            'state' => $this->individual->relationLoaded('state') && $this->individual->state
                ? ['id' => $this->individual->state->id, 'name' => $this->individual->state->name]
                : null,
            'health_status' => $this->individual->health_status,
            'education_level' => $this->individual->education_level,
            'marital_status' => $this->individual->marital_status,
            'employment_status' => $this->individual->employment_status,
            'monthly_income' => $this->individual->monthly_income,
            'date_of_father_death' => $this->individual->date_of_father_death?->toDateString(),
            'school_year' => $this->individual->school_year,
            'sibling_number' => $this->individual->sibling_number,
            'behavior_notes' => $this->individual->behavior_notes,
            'notes' => $this->individual->notes,
        ];

        if (! $canViewSensitive || $request->user() === null) {
            return BeneficiarySensitiveFields::filterProfileData(
                $request->user() ?? new User,
                $this->resource,
                $payload,
            );
        }

        return BeneficiarySensitiveFields::filterProfileData(
            $request->user(),
            $this->resource,
            $payload,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatFamily(Request $request, bool $canViewSensitive): ?array
    {
        if ($this->family === null) {
            return null;
        }

        $payload = [
            'id' => $this->family->id,
            'household_name' => $this->family->household_name,
            'national_id' => $this->family->national_id,
            'phone' => $this->family->phone,
            'address' => $this->family->address,
            'village' => $this->family->village,
            'country_id' => $this->family->country_id,
            'state_id' => $this->family->state_id,
            'country' => $this->family->relationLoaded('country') && $this->family->country
                ? ['id' => $this->family->country->id, 'name' => $this->family->country->name]
                : null,
            'state' => $this->family->relationLoaded('state') && $this->family->state
                ? ['id' => $this->family->state->id, 'name' => $this->family->state->name]
                : null,
            'social_status' => $this->family->social_status,
            'total_members' => $this->family->total_members,
            'monthly_income' => $this->family->monthly_income,
            'housing_type' => $this->family->housing_type,
            'housing_ownership' => $this->family->housing_ownership,
            'monthly_rent' => $this->family->monthly_rent,
            'notes' => $this->family->notes,
            'members' => $this->family->relationLoaded('members')
                ? $this->family->members->map(fn ($member) => [
                    'id' => $member->id,
                    'subtype' => $member->subtype?->value,
                    'first_name' => $member->first_name,
                    'middle_name' => $member->middle_name,
                    'last_name' => $member->last_name,
                    'full_name' => $member->full_name,
                    'gender' => $member->gender?->value,
                    'birthdate' => $member->birthdate?->toDateString(),
                    'national_id' => $member->national_id,
                    'relation' => $member->relation,
                    'health_status' => $member->health_status,
                    'education_level' => $member->education_level,
                    'marital_status' => $member->marital_status,
                    'employment_status' => $member->employment_status,
                    'monthly_income' => $member->monthly_income,
                    'date_of_father_death' => $member->date_of_father_death?->toDateString(),
                    'school_year' => $member->school_year,
                    'sibling_number' => $member->sibling_number,
                    'behavior_notes' => $member->behavior_notes,
                ])->all()
                : [],
        ];

        if (! $canViewSensitive || $request->user() === null) {
            return BeneficiarySensitiveFields::filterProfileData(
                $request->user() ?? new User,
                $this->resource,
                $payload,
            );
        }

        return BeneficiarySensitiveFields::filterProfileData(
            $request->user(),
            $this->resource,
            $payload,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatOrganization(Request $request, bool $canViewSensitive): ?array
    {
        if ($this->organization === null) {
            return null;
        }

        $payload = [
            'id' => $this->organization->id,
            'name' => $this->organization->name,
            'organization_type' => $this->organization->organization_type,
            'charity_number' => $this->organization->charity_number,
            'phone' => $this->organization->phone,
            'email' => $this->organization->email,
            'address' => $this->organization->address,
            'country_id' => $this->organization->country_id,
            'state_id' => $this->organization->state_id,
            'country' => $this->organization->relationLoaded('country') && $this->organization->country
                ? ['id' => $this->organization->country->id, 'name' => $this->organization->country->name]
                : null,
            'state' => $this->organization->relationLoaded('state') && $this->organization->state
                ? ['id' => $this->organization->state->id, 'name' => $this->organization->state->name]
                : null,
            'contact_person' => $this->organization->contact_person,
            'contact_phone' => $this->organization->contact_phone,
            'notes' => $this->organization->notes,
        ];

        if (! $canViewSensitive || $request->user() === null) {
            return BeneficiarySensitiveFields::filterProfileData(
                $request->user() ?? new User,
                $this->resource,
                $payload,
            );
        }

        return BeneficiarySensitiveFields::filterProfileData(
            $request->user(),
            $this->resource,
            $payload,
        );
    }

    private function resolvePrimaryContact(bool $canViewSensitive, ?User $user): ?string
    {
        $contact = match ($this->type) {
            BeneficiaryType::Individual => $this->individual?->phone,
            BeneficiaryType::Family => $this->family?->phone,
            BeneficiaryType::Organization => $this->organization?->phone ?? $this->organization?->contact_phone,
            default => null,
        };

        if ($contact === null) {
            return null;
        }

        if ($canViewSensitive && ($user === null || BeneficiarySensitiveFields::userCanViewField($user, $this->resource, 'phone'))) {
            return $contact;
        }

        return (string) BeneficiarySensitiveFields::mask($contact);
    }
}
