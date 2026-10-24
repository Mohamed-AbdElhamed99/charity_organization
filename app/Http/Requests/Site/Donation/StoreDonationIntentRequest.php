<?php

namespace App\Http\Requests\Site\Donation;

use App\Models\CampaignAppeal;
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
            // Existence/availability of the campaign is resolved by DonationService,
            // which falls back to a general donation if the campaign is missing,
            // deleted, or no longer accepting donations.
            'campaign_id' => ['nullable', 'integer'],
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
            'appeal_id' => ['nullable', 'integer', Rule::exists('campaign_appeals', 'id')],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $isGeneral = (bool) $this->boolean('is_general');
            $campaignId = $this->input('campaign_id');
            $appealId = $this->input('appeal_id');

            if ($appealId) {
                if ($isGeneral) {
                    $validator->errors()->add('appeal_id', __('An appeal gift cannot be a general donation.'));

                    return;
                }

                $appeal = CampaignAppeal::query()->with('campaign')->find($appealId);

                if ($appeal === null) {
                    return;
                }

                if ($campaignId && (int) $campaignId !== $appeal->campaign_id) {
                    $validator->errors()->add('campaign_id', __('This appeal belongs to a different campaign.'));

                    return;
                }

                $quantity = $this->filled('quantity') ? (int) $this->input('quantity') : null;

                $appeal->resolvedGift($quantity, (int) $this->input('amount'));

                return;
            }

            if ($this->filled('quantity')) {
                $validator->errors()->add('quantity', __('A quantity can only be set for an appeal.'));
            }

            if ($isGeneral && $campaignId) {
                $validator->errors()->add('campaign_id', __('Campaign and general donation cannot both be set.'));
            }

            if (! $isGeneral && ! $campaignId) {
                $validator->errors()->add('campaign_id', __('Select a campaign or choose general donation.'));
            }
        });
    }
}
