<?php

namespace App\Http\Requests\Admin\Beneficiary;

use App\Enums\BeneficiaryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBeneficiaryStatusRequest extends FormRequest
{
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
        return [
            'status' => ['required', 'string', Rule::enum(BeneficiaryStatus::class)],
        ];
    }
}
