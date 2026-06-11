<?php

namespace App\Http\Requests\Admin\Beneficiary;

use App\Http\Requests\Admin\Beneficiary\Concerns\ValidatesBeneficiaryPayload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBeneficiaryRequest extends FormRequest
{
    use ValidatesBeneficiaryPayload;

    public function authorize(): bool
    {
        $beneficiary = $this->route('beneficiary');

        return $beneficiary !== null
            && ($this->user()?->can('update', $beneficiary) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(
            $this->baseRules(isUpdate: true),
            $this->rulesForExistingBeneficiaryType(),
        );
    }
}
