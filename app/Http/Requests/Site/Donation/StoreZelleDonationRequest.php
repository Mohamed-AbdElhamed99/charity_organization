<?php

namespace App\Http\Requests\Site\Donation;

class StoreZelleDonationRequest extends StoreDonationIntentRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'donor_covers_fee' => false,
            'is_anonymous' => $this->boolean('is_anonymous'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
