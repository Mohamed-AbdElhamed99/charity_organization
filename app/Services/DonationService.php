<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DTOs\CreateDonationIntentDTO;
use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\StripeStatus;
use App\Models\Campaign;
use App\Models\Currency;
use App\Models\Donation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DonationService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly DonorIdentityService $donorIdentityService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function createIntent(CreateDonationIntentDTO $dto): array
    {
        $currency = Currency::query()->where('code', 'USD')->where('is_active', true)->firstOrFail();
        $donor = $this->donorIdentityService->firstOrCreateByEmail(
            $dto->firstName,
            $dto->lastName,
            $dto->email,
            $dto->phone,
            $dto->countryId,
        );

        if ($dto->isGeneral) {
            $campaignId = null;
        } else {
            $campaign = Campaign::query()
                ->whereKey($dto->campaignId)
                ->where('status', CampaignStatus::Active)
                ->where('is_public', true)
                ->where('open_donation_form', true)
                ->first();

            if ($campaign === null) {
                throw new InvalidArgumentException(__('The selected campaign is not available for donations.'));
            }

            $campaignId = $campaign->id;
        }

        $chargeCents = $dto->donorCoversFee
            ? $this->gateway->grossUpForFee($dto->amountCents, $currency->code)
            : $dto->amountCents;

        $estimatedFee = $this->gateway->estimateFee($chargeCents, $currency->code);

        $metadata = [
            'donor_id' => (string) $donor->id,
            'email' => $dto->email,
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'campaign_id' => $campaignId ? (string) $campaignId : '',
            'is_general' => $dto->isGeneral ? '1' : '0',
            'amount_cents' => (string) $dto->amountCents,
            'donor_covers_fee' => $dto->donorCoversFee ? '1' : '0',
            'currency_code' => $currency->code,
            'is_anonymous' => $dto->isAnonymous ? '1' : '0',
        ];

        $paymentIntent = $this->gateway->createPaymentIntent($chargeCents, $currency->code, $metadata);

        $donation = DB::transaction(function () use ($dto, $donor, $campaignId, $paymentIntent) {
            return Donation::create([
                'transaction_id' => null,
                'donor_id' => $donor->id,
                'campaign_id' => $campaignId,
                'is_general' => $dto->isGeneral,
                'amount' => $dto->amountCents,
                'status' => DonationStatus::Pending,
                'stripe_status' => StripeStatus::Pending,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'donor_covers_fee' => $dto->donorCoversFee,
                'is_anonymous' => $dto->isAnonymous,
                'donor_message' => $dto->donorMessage,
                'metadata' => [
                    'estimated_charge_cents' => $paymentIntent->amount,
                ],
            ]);
        });

        return [
            'clientSecret' => $paymentIntent->clientSecret,
            'publishableKey' => config('services.stripe.key'),
            'paymentIntentId' => $paymentIntent->id,
            'donationId' => $donation->id,
            'amount' => $dto->amountCents,
            'chargeCents' => $chargeCents,
            'estimatedFee' => $estimatedFee,
            'donorCoversFee' => $dto->donorCoversFee,
            'currency' => $currency->code,
        ];
    }
}
