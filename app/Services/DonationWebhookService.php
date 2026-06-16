<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\DonationStatus;
use App\Enums\StripeStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Jobs\SendDonationReceipt;
use App\Models\Account;
use App\Models\Campaign;
use App\Models\Currency;
use App\Models\Donation;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\WebhookEvent;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\StripeObject;

class DonationWebhookService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly DonorIdentityService $donorIdentityService,
    ) {}

    public function handle(Event $event): void
    {
        $webhookEvent = WebhookEvent::query()->firstOrCreate(
            ['event_id' => $event->id],
            [
                'type' => $event->type,
                'payload' => $event->toArray(),
            ],
        );

        if ($webhookEvent->processed_at !== null) {
            return;
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event, $webhookEvent),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event, $webhookEvent),
            default => null,
        };
    }

    private function handlePaymentIntentSucceeded(Event $event, WebhookEvent $webhookEvent): void
    {
        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->object;
        
        $donation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntent->id)
            ->first();

        if ($donation === null) {
            $donation = $this->reconstructDonationFromMetadata($paymentIntent);
        }

        if ($donation === null) {
            $webhookEvent->update(['processed_at' => now()]);

            return;
        }

        if ($donation->status === DonationStatus::Succeeded) {
            $webhookEvent->update(['processed_at' => now()]);

            return;
        }

        $chargeId = $this->resolveChargeId($paymentIntent);

        if ($chargeId === null) {
            return;
        }

        $grossCents = (int) $paymentIntent->amount_received;
        $actualFeeCents = $this->gateway->actualFeeFor($chargeId);
        $netCents = $grossCents - $actualFeeCents;

        DB::transaction(function () use ($donation, $chargeId, $grossCents, $actualFeeCents, $netCents, $webhookEvent) {
            $account = $this->resolveStripeAccount();
            $currency = Currency::query()->where('code', 'USD')->firstOrFail();
            $paymentMethod = PaymentMethod::query()->where('code', 'stripe')->first();

            $account = Account::query()->lockForUpdate()->findOrFail($account->id);

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => TransactionType::Donation,
                'direction' => TransactionDirection::In,
                'currency_id' => $currency->id,
                'gross_amount' => Money::centsToDecimal($grossCents),
                'fee_amount' => Money::centsToDecimal($actualFeeCents),
                'net_amount' => Money::centsToDecimal($netCents),
                'transaction_date' => now()->toDateString(),
                'reference_number' => $chargeId,
                'description' => $donation->is_general
                    ? 'Online general donation'
                    : 'Online campaign donation',
                'payment_method_id' => $paymentMethod?->id,
                'created_by' => null,
                'is_reconciled' => false,
            ]);

            $runningBalance = $this->computeRunningBalance($account, TransactionDirection::In, Money::centsToDecimal($netCents));
            $transaction->update(['running_balance' => $runningBalance]);

            $donation->update([
                'transaction_id' => $transaction->id,
                'stripe_charge_id' => $chargeId,
                'status' => DonationStatus::Succeeded,
                'stripe_status' => StripeStatus::Succeeded,
            ]);

            if ($donation->campaign_id !== null) {
                $campaign = Campaign::query()->lockForUpdate()->findOrFail($donation->campaign_id);
                $campaign->increment('collected_amount', $donation->amount);
            }

            $webhookEvent->update(['processed_at' => now()]);
        });

        SendDonationReceipt::dispatch($donation->fresh());
    }

    private function handlePaymentIntentFailed(Event $event, WebhookEvent $webhookEvent): void
    {
        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->object;

        $donation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntent->id)
            ->first();

        if ($donation !== null && $donation->status !== DonationStatus::Succeeded) {
            $donation->update([
                'status' => DonationStatus::Failed,
                'stripe_status' => StripeStatus::Failed,
            ]);
        }

        $webhookEvent->update(['processed_at' => now()]);
    }

    private function reconstructDonationFromMetadata(PaymentIntent $paymentIntent): ?Donation
    {
        $metadata = $paymentIntent->metadata->toArray();

        if (! isset($metadata['amount_cents'])) {
            return null;
        }

        $donorId = isset($metadata['donor_id']) ? (int) $metadata['donor_id'] : null;

        if ($donorId === null && isset($metadata['email'], $metadata['first_name'], $metadata['last_name'])) {
            $donor = $this->donorIdentityService->firstOrCreateByEmail(
                $metadata['first_name'],
                $metadata['last_name'],
                $metadata['email'],
            );
            $donorId = $donor->id;
        }

        if ($donorId === null) {
            return null;
        }
        $isGeneral = ($metadata['is_general'] ?? '0') === '1';
        $campaignId = ! empty($metadata['campaign_id']) ? (int) $metadata['campaign_id'] : null;
        $amountCents = (int) $metadata['amount_cents'];
        $donorCoversFee = ($metadata['donor_covers_fee'] ?? '0') === '1';
        $isAnonymous = ($metadata['is_anonymous'] ?? '0') === '1';

        return Donation::create([
            'transaction_id' => null,
            'donor_id' => $donorId,
            'campaign_id' => $isGeneral ? null : $campaignId,
            'is_general' => $isGeneral,
            'amount' => $amountCents,
            'status' => DonationStatus::Pending,
            'stripe_status' => StripeStatus::Pending,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'donor_covers_fee' => $donorCoversFee,
            'is_anonymous' => $isAnonymous,
            'metadata' => ['reconstructed_from_webhook' => true],
        ]);
    }

    private function resolveChargeId(StripeObject $paymentIntent): ?string
    {
        if ($paymentIntent->latest_charge !== null) {
            return is_string($paymentIntent->latest_charge)
                ? $paymentIntent->latest_charge
                : $paymentIntent->latest_charge->id;
        }

        return null;
    }

    private function resolveStripeAccount(): Account
    {
        $configuredId = config('donations.stripe_account_id');

        if ($configuredId) {
            return Account::query()->active()->findOrFail((int) $configuredId);
        }

        return Account::query()
            ->active()
            ->whereHas('currency', fn ($q) => $q->where('code', 'USD'))
            ->orderBy('id')
            ->firstOrFail();
    }

    private function computeRunningBalance(Account $account, TransactionDirection $direction, string $netAmountDecimal): string
    {
        $lastBalance = Transaction::query()
            ->where('account_id', $account->id)
            ->whereNotNull('running_balance')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->value('running_balance');

        $balance = (string) ($lastBalance ?? $account->opening_balance);

        return $direction === TransactionDirection::In
            ? bcadd($balance, $netAmountDecimal, 2)
            : bcsub($balance, $netAmountDecimal, 2);
    }
}