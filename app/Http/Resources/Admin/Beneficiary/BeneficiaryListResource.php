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

        $contact = match ($this->type?->value) {
            'individual' => $this->individual?->phone,
            'family' => $this->family?->phone,
            'organization' => $this->organization?->phone ?? $this->organization?->contact_phone,
            default => null,
        };

        if ($contact !== null && ! ($canViewSensitive && BeneficiarySensitiveFields::userCanViewField($user, $this->resource, 'phone'))) {
            $contact = (string) BeneficiarySensitiveFields::mask($contact);
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'display_name' => $canViewSensitive ? $this->display_name : $this->code,
            'primary_contact' => $contact,
            'created_at' => $this->created_at?->toDateTimeString(),
            'can_view_sensitive' => $canViewSensitive,
        ];
    }
}
