<?php

namespace App\Http\Requests\Admin\Beneficiary;

use App\Http\Requests\Admin\Beneficiary\Concerns\ValidatesBeneficiaryPayload;
use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryRequest extends FormRequest
{
    use ValidatesBeneficiaryPayload;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(
            $this->baseRules(),
            $this->rulesForType($this->input('type')),
        );
    }
}
