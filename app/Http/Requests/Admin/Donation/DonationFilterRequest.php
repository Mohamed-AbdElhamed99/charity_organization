<?php

namespace App\Http\Requests\Admin\Donation;

use App\Enums\DonationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DonationFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view_donations') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'campaign_id' => ['nullable', 'integer', Rule::exists('campaigns', 'id')],
            'status' => ['nullable', 'string', Rule::enum(DonationStatus::class)],
            'currency' => ['nullable', 'integer', Rule::exists('currencies', 'id')],
            'donor_covers_fee' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', Rule::in(['general', 'campaign'])],
            'donor' => ['nullable', 'string', 'max:255'],
            'amount_min' => ['nullable', 'integer', 'min:0'],
            'amount_max' => ['nullable', 'integer', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'format' => ['nullable', 'string', Rule::in(['csv', 'xlsx'])],
        ];
    }
}
