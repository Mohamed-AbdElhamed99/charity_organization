<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DTOs\CreateDonationIntentDTO;
use App\DTOs\CreateDonationSubscriptionDTO;
use App\DTOs\DonationAllocationInput;
use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\DonationSubscriptionStatus;
use App\Enums\StripeStatus;
use App\Models\Campaign;
use App\Models\Currency;
use App\Models\Donation;
use App\Models\DonationSubscription;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\DonationReceiptMail;
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
        
        [$isGeneral, $campaignId] = $this->resolveCampaignAssignment($dto->isGeneral, $dto->campaignId);

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
            'is_general' => $isGeneral ? '1' : '0',
            'amount_cents' => (string) $dto->amountCents,
            'donor_covers_fee' => $dto->donorCoversFee ? '1' : '0',
            'currency_code' => $currency->code,
            'is_anonymous' => $dto->isAnonymous ? '1' : '0',
        ];

        $paymentIntent = $this->gateway->createPaymentIntent($chargeCents, $currency->code, $metadata);

        $donation = DB::transaction(function () use ($dto, $donor, $campaignId, $isGeneral, $paymentIntent) {
            return Donation::create([
                'transaction_id' => null,
                'donor_id' => $donor->id,
                'campaign_id' => $campaignId,
                'is_general' => $isGeneral,
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
Mail::to($donor->email)->send(new DonationReceiptMail($donation));
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

    /**
     * Creates a monthly recurring donation. The first invoice's PaymentIntent
     * is confirmed client-side exactly like a one-time gift; subsequent
     * monthly charges are billed automatically by Stripe and reconciled via
     * the `invoice.paid` webhook (see DonationWebhookService).
     *
     * Note: recurring donations are not gross-ed up for fee-cover the same
     * way one-time gifts are, since the actual processing fee can vary
     * slightly cycle to cycle; the donor's chosen amount is charged as-is
     * each month and `donor_covers_fee` is recorded for reporting only.
     *
     * @return array<string, mixed>
     */
    public function createSubscriptionIntent(CreateDonationSubscriptionDTO $dto): array
    {
        $currency = Currency::query()->where('code', 'USD')->where('is_active', true)->firstOrFail();
        $donor = $this->donorIdentityService->firstOrCreateByEmail(
            $dto->firstName,
            $dto->lastName,
            $dto->email,
            $dto->phone,
            $dto->countryId,
        );

        $resolvedAllocations = array_map(
            fn (DonationAllocationInput $allocation) => [
                'amountCents' => $allocation->amountCents,
                ...array_combine(['isGeneral', 'campaignId'], $this->resolveCampaignAssignment($allocation->isGeneral, $allocation->campaignId)),
            ],
            $dto->allocations,
        );

        $totalAmountCents = array_sum(array_column($resolvedAllocations, 'amountCents'));

        $customerId = $this->resolveStripeCustomerId($donor, $dto);

        $metadata = [
            'donor_id' => (string) $donor->id,
            'email' => $dto->email,
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'amount_cents' => (string) $totalAmountCents,
            'donor_covers_fee' => $dto->donorCoversFee ? '1' : '0',
            'currency_code' => $currency->code,
            'is_anonymous' => $dto->isAnonymous ? '1' : '0',
            'is_recurring' => '1',
            'frequency' => $dto->frequency->value,
            'allocation_count' => (string) count($resolvedAllocations),
        ];

        $subscriptionIntent = $this->gateway->createSubscription(
            $customerId,
            $totalAmountCents,
            $currency->code,
            $dto->frequency,
            $metadata,
        );

        $subscription = DB::transaction(function () use ($dto, $donor, $customerId, $subscriptionIntent, $totalAmountCents, $resolvedAllocations) {
            $subscription = DonationSubscription::create([
                'donor_id' => $donor->id,
                'amount_cents' => $totalAmountCents,
                'donor_covers_fee' => $dto->donorCoversFee,
                'frequency' => $dto->frequency,
                'stripe_customer_id' => $customerId,
                'stripe_subscription_id' => $subscriptionIntent->subscriptionId,
                'billing_cycle_anchor_at' => $subscriptionIntent->billingCycleAnchor
                    ? Carbon::createFromTimestamp($subscriptionIntent->billingCycleAnchor)
                    : now(),
                'status' => DonationSubscriptionStatus::Active,
                'metadata' => [
                    'is_anonymous' => $dto->isAnonymous,
                    'donor_message' => $dto->donorMessage,
                ],
            ]);

            foreach ($resolvedAllocations as $allocation) {
                $subscription->allocations()->create([
                    'campaign_id' => $allocation['campaignId'],
                    'is_general' => $allocation['isGeneral'],
                    'amount_cents' => $allocation['amountCents'],
                ]);
            }

            return $subscription;
        });

        return [
            'clientSecret' => $subscriptionIntent->clientSecret,
            'publishableKey' => config('services.stripe.key'),
            'paymentIntentId' => $subscriptionIntent->paymentIntentId,
            'subscriptionId' => $subscriptionIntent->subscriptionId,
            'donationSubscriptionId' => $subscription->id,
            'amount' => $totalAmountCents,
            'chargeCents' => $totalAmountCents,
            'estimatedFee' => $this->gateway->estimateFee($totalAmountCents, $currency->code),
            'donorCoversFee' => $dto->donorCoversFee,
            'currency' => $currency->code,
            'frequency' => $dto->frequency->value,
        ];
    }

    /**
     * Resolves the campaign to credit, falling back to a general donation
     * when the campaign is missing, deleted, or no longer accepting
     * donations instead of failing checkout.
     *
     * @return array{0: bool, 1: int|null} [isGeneral, campaignId]
     */
    private function resolveCampaignAssignment(bool $isGeneral, ?int $campaignId): array
    {
        if ($isGeneral) {
            return [true, null];
        }

        $campaign = Campaign::query()
            ->whereKey($campaignId)
            ->where('status', CampaignStatus::Active)
            ->where('is_public', true)
            ->where('open_donation_form', true)
            ->first();

        if ($campaign === null) {
            return [true, null];
        }

        return [false, $campaign->id];
    }

    private function resolveStripeCustomerId(User $donor, CreateDonationSubscriptionDTO $dto): string
    {
        $profile = DonorProfile::query()->firstOrCreate(['user_id' => $donor->id]);

        if ($profile->stripe_customer_id) {
            return $profile->stripe_customer_id;
        }

        $customerId = $this->gateway->findOrCreateCustomer($dto->email, [
            'name' => trim($dto->firstName.' '.$dto->lastName),
            'phone' => $dto->phone,
        ]);

        $profile->update(['stripe_customer_id' => $customerId]);

        return $customerId;
    }
}
