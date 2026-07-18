<?php

namespace App\Http\Requests\Site\Donation;

use App\Enums\RecurrenceFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDonationSubscriptionRequest extends FormRequest
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
            'frequency' => ['required', Rule::enum(RecurrenceFrequency::class)],
            // Existence/availability of each allocation's campaign is resolved by
            // DonationService, which falls back to a general donation if the
            // campaign is missing, deleted, or no longer accepting donations.
            'allocations' => ['required', 'array', 'min:1', 'max:10'],
            'allocations.*.campaign_id' => ['nullable', 'integer'],
            'allocations.*.is_general' => ['required', 'boolean'],
            'allocations.*.amount' => ['required', 'integer', 'min:'.$minAmount],
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
            $allocations = $this->input('allocations', []);

            if (! is_array($allocations)) {
                return;
            }

            foreach ($allocations as $index => $allocation) {
                $isGeneral = (bool) ($allocation['is_general'] ?? false);
                $campaignId = $allocation['campaign_id'] ?? null;

                if ($isGeneral && $campaignId) {
                    $validator->errors()->add("allocations.{$index}.campaign_id", __('Campaign and general donation cannot both be set.'));
                }

                if (! $isGeneral && ! $campaignId) {
                    $validator->errors()->add("allocations.{$index}.campaign_id", __('Select a campaign or choose general donation.'));
                }
            }
        });
    }
}
