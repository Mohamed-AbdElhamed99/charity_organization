<?php

namespace App\Http\Resources\Admin\Beneficiary;

use App\Models\Beneficiary;
use App\Support\BeneficiarySensitiveFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Beneficiary */
class BeneficiaryListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canViewSensitive = $user !== null && $user->can('viewSensitiveDetails', $this->resource);

        $profile = match ($this->type?->value) {
            'individual' => $this->individual,
            'family' => $this->family,
            'organization' => $this->organization,
            default => null,
        };

        $contact = match ($this->type?->value) {
            'individual' => $this->individual?->phone,
            'family' => $this->family?->phone,
            'organization' => $this->organization?->phone ?? $this->organization?->contact_phone,
            default => null,
        };

        if ($contact !== null && ! ($canViewSensitive && BeneficiarySensitiveFields::userCanViewField($user, $this->resource, 'phone'))) {
            $contact = (string) BeneficiarySensitiveFields::mask($contact);
        }

        $nationalId = match ($this->type?->value) {
            'individual' => $this->individual?->national_id,
            'family' => $this->family?->national_id,
            default => null,
        };

        if ($nationalId !== null && ! ($canViewSensitive && BeneficiarySensitiveFields::userCanViewField($user, $this->resource, 'national_id'))) {
            $nationalId = (string) BeneficiarySensitiveFields::mask($nationalId);
        }

        $address = $profile?->address;

        if ($address !== null && ! ($canViewSensitive && BeneficiarySensitiveFields::userCanViewField($user, $this->resource, 'address'))) {
            $address = (string) BeneficiarySensitiveFields::mask($address);
        }

        $countryName = $profile?->country?->name;
        $stateName = $profile?->state?->name;

        if ($countryName !== null && ! ($canViewSensitive && BeneficiarySensitiveFields::userCanViewField($user, $this->resource, 'country_id'))) {
            $countryName = null;
        }

        if ($stateName !== null && ! ($canViewSensitive && BeneficiarySensitiveFields::userCanViewField($user, $this->resource, 'state_id'))) {
            $stateName = null;
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'display_name' => $canViewSensitive ? $this->display_name : $this->code,
            'national_id' => $nationalId,
            'address' => $address,
            'country_name' => $countryName,
            'state_name' => $stateName,
            'primary_contact' => $contact,
            'created_at' => $this->created_at?->toDateTimeString(),
            'can_view_sensitive' => $canViewSensitive,
        ];
    }
}
