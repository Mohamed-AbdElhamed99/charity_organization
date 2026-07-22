<?php

namespace App\Http\Requests\Admin\BeneficiarySupport;

use App\Enums\BeneficiaryStatus;
use App\Enums\CampaignStatus;
use App\Enums\SupportStatus;
use App\Models\CampaignExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBeneficiarySupportRequest extends FormRequest
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
        return [
            'beneficiary_id' => [
                'required',
                'integer',
                Rule::exists('beneficiaries', 'id')->where(
                    fn ($query) => $query->where('status', BeneficiaryStatus::Active->value)
                ),
            ],
            'campaign_id' => [
                'required',
                'integer',
                Rule::exists('campaigns', 'id')->where(
                    fn ($query) => $query->where('status', CampaignStatus::Active->value)
                ),
            ],
            'supported_at' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(SupportStatus::class)],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.aid_item_id' => ['nullable', 'integer', Rule::exists('aid_items', 'id')],
            'items.*.item_name_snapshot' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'integer', 'min:0'],
            'items.*.campaign_expense_id' => ['nullable', 'integer', Rule::exists('campaign_expenses', 'id')],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $campaignId = $this->integer('campaign_id');
                if ($campaignId <= 0) {
                    return;
                }

                foreach ($this->input('items', []) as $index => $item) {
                    $campaignExpenseId = (int) ($item['campaign_expense_id'] ?? 0);
                    if ($campaignExpenseId <= 0) {
                        continue;
                    }

                    $belongsToCampaign = CampaignExpense::query()
                        ->whereKey($campaignExpenseId)
                        ->where('campaign_id', $campaignId)
                        ->exists();

                    if (! $belongsToCampaign) {
                        $validator->errors()->add(
                            "items.{$index}.campaign_expense_id",
                            __('The selected campaign expense must belong to the selected campaign.')
                        );
                    }
                }
            },
        ];
    }
}
