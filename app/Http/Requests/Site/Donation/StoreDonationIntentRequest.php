<?php

namespace App\Http\Requests\Site\Donation;

use App\Enums\CampaignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDonationIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $minAmount = config('donations.min_amount_cents', 100);

        return [
            'campaign_id' => [
                'nullable',
                'integer',
                Rule::exists('campaigns', 'id')->where(function ($query) {
                    $query->where('status', CampaignStatus::Active->value)
                        ->where('is_public', true)
                        ->where('open_donation_form', true);
                }),
            ],
            'is_general' => ['required', 'boolean'],
            'amount' => ['required', 'integer', 'min:'.$minAmount],
            'donor_covers_fee' => ['required', 'boolean'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'is_anonymous' => ['boolean'],
            'donor_message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $isGeneral = (bool) $this->boolean('is_general');
            $campaignId = $this->input('campaign_id');

            if ($isGeneral && $campaignId) {
                $validator->errors()->add('campaign_id', __('Campaign and general donation cannot both be set.'));
            }

            if (! $isGeneral && ! $campaignId) {
                $validator->errors()->add('campaign_id', __('Select a campaign or choose general donation.'));
            }
        });
    }
}
