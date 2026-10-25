<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\DonationStatus;
use App\Enums\DonationSubscriptionStatus;
use App\Enums\StripeStatus;
use App\Enums\TransactionDirection;
use App\Jobs\SendDonationReceipt;
use App\Models\BankAccount;
use App\Models\Campaign;
use App\Models\Currency;
use App\Models\Donation;
use App\Models\DonationSubscription;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\WebhookEvent;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\StripeObject;
use Stripe\Subscription;

class DonationWebhookService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly DonorIdentityService $donorIdentityService,
        private readonly DonationSubmittedNotifier $submittedNotifier,
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
        Log::info('Webhook Event ', [$webhookEvent]);
        Log::info('processed_at ', [$webhookEvent->processed_at]);
        if ($webhookEvent->processed_at !== null) {
            return;
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event, $webhookEvent),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event, $webhookEvent),
            'invoice.paid' => $this->handleInvoicePaid($event, $webhookEvent),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event, $webhookEvent),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event, $webhookEvent),
            default => null,
        };
    }

    private function handlePaymentIntentSucceeded(Event $event, WebhookEvent $webhookEvent): void
    {
        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->object;

        $invoiceId = $this->resolveInvoiceIdFromPaymentIntent($paymentIntent);

        // Subscription invoice PaymentIntents have empty metadata and no local
        // one-time donation row until reconcile creates/finalizes them. Prefer
        // the invoice path so fee splits and renewals stay consistent.
        if ($invoiceId !== null) {
            $chargeId = $this->resolveChargeId($paymentIntent);

            if ($chargeId === null) {
                return;
            }

            $this->reconcilePaidSubscriptionInvoice(
                invoiceId: $invoiceId,
                stripeSubscriptionId: null,
                paymentIntentId: $paymentIntent->id,
                chargeId: $chargeId,
                amountPaidCents: (int) $paymentIntent->amount_received,
                webhookEvent: $webhookEvent,
            );

            return;
        }

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

        $this->finalizeSucceededDonation($donation, $chargeId, $grossCents, $webhookEvent);
    }

    private function handlePaymentIntentFailed(Event $event, WebhookEvent $webhookEvent): void
    {
        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->object;

        $donations = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntent->id)
            ->where('status', '!=', DonationStatus::Succeeded)
            ->get();

        foreach ($donations as $donation) {
            $donation->update([
                'status' => DonationStatus::Failed,
                'stripe_status' => StripeStatus::Failed,
            ]);
        }

        // Only the first allocation of a multi-allocation subscription stores
        // the PaymentIntent id (unique column), so a failed first-cycle charge
        // must also fail the sibling Pending rows for the other allocations -
        // otherwise they are orphaned in Pending forever.
        $subscriptionId = $donations
            ->first(fn (Donation $donation) => $donation->donation_subscription_id !== null)
            ?->donation_subscription_id;

        if ($subscriptionId !== null) {
            Donation::query()
                ->where('donation_subscription_id', $subscriptionId)
                ->where('status', DonationStatus::Pending)
                ->whereNull('stripe_invoice_id')
                ->update([
                    'status' => DonationStatus::Failed,
                    'stripe_status' => StripeStatus::Failed,
                ]);
        }

        $webhookEvent->update(['processed_at' => now()]);
    }

    /**
     * A subscription invoice was paid (first cycle or any renewal). Creates
     * the corresponding Donation row (one row per billing cycle, matching
     * the app's one-row-per-gift ledger model) and reconciles it exactly
     * like a one-time gift.
     */
    private function handleInvoicePaid(Event $event, WebhookEvent $webhookEvent): void
    {
        /** @var Invoice $invoice */
        $invoice = $event->data->object;

        $subscriptionId = $this->resolveSubscriptionIdFromInvoice($invoice);
        $paymentDetails = $this->gateway->resolveInvoicePaymentDetails($invoice->id);
        $chargeId = $paymentDetails['chargeId'] ?? null;

        if ($chargeId === null) {
            return;
        }

        $this->reconcilePaidSubscriptionInvoice(
            invoiceId: $invoice->id,
            stripeSubscriptionId: $subscriptionId ?? ($paymentDetails['subscriptionId'] ?? null),
            paymentIntentId: $paymentDetails['paymentIntentId'] ?? null,
            chargeId: $chargeId,
            amountPaidCents: (int) ($invoice->amount_paid ?? $paymentDetails['amountPaid'] ?? 0),
            webhookEvent: $webhookEvent,
        );
    }

    /**
     * Idempotently record one Donation per subscription allocation for a paid
     * invoice. Finalizes pending first-cycle rows (created at checkout for
     * thank-you polling) or creates renewal rows.
     */
    private function reconcilePaidSubscriptionInvoice(
        string $invoiceId,
        ?string $stripeSubscriptionId,
        ?string $paymentIntentId,
        string $chargeId,
        int $amountPaidCents,
        WebhookEvent $webhookEvent,
    ): void {
        if (Donation::query()->where('stripe_invoice_id', $invoiceId)->where('status', DonationStatus::Succeeded)->exists()) {
            $webhookEvent->update(['processed_at' => now()]);

            return;
        }

        if ($stripeSubscriptionId === null && $paymentIntentId !== null) {
            $stripeSubscriptionId = Donation::query()
                ->where('stripe_payment_intent_id', $paymentIntentId)
                ->whereNotNull('donation_subscription_id')
                ->with('donationSubscription')
                ->first()
                ?->donationSubscription
                ?->stripe_subscription_id;
        }

        if ($stripeSubscriptionId === null) {
            $paymentDetails = $this->gateway->resolveInvoicePaymentDetails($invoiceId);
            $stripeSubscriptionId = $paymentDetails['subscriptionId'] ?? null;
            $paymentIntentId ??= $paymentDetails['paymentIntentId'] ?? null;

            if (($paymentDetails['chargeId'] ?? null) !== null) {
                $chargeId = $paymentDetails['chargeId'];
            }

            if (($paymentDetails['amountPaid'] ?? null) !== null && $amountPaidCents === 0) {
                $amountPaidCents = (int) $paymentDetails['amountPaid'];
            }
        }

        if ($stripeSubscriptionId === null) {
            $webhookEvent->update(['processed_at' => now()]);

            return;
        }

        $subscription = DonationSubscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->with('allocations')
            ->first();

        if ($subscription === null || $subscription->allocations->isEmpty()) {
            $webhookEvent->update(['processed_at' => now()]);

            return;
        }

        $pendingDonations = Donation::query()
            ->where('donation_subscription_id', $subscription->id)
            ->where('status', DonationStatus::Pending)
            ->where(function ($query) use ($invoiceId, $paymentIntentId) {
                $query->whereNull('stripe_invoice_id')
                    ->orWhere('stripe_invoice_id', $invoiceId);

                if ($paymentIntentId !== null) {
                    $query->orWhere('stripe_payment_intent_id', $paymentIntentId);
                }
            })
            ->orderBy('id')
            ->get();

        $totalGrossCents = $amountPaidCents > 0 ? $amountPaidCents : $subscription->amount_cents;
        $totalFeeCents = $this->gateway->actualFeeFor($chargeId)
            ?? (int) round(((float) config('services.stripe.fee_percent') / 100) * $totalGrossCents);

        $metadata = $subscription->metadata ?? [];

        if ($pendingDonations->isNotEmpty()) {
            $donationCount = $pendingDonations->count();
            $assignedFeeCents = 0;

            foreach ($pendingDonations as $index => $donation) {
                $feeCents = $index === $donationCount - 1
                    ? $totalFeeCents - $assignedFeeCents
                    : (int) round($totalFeeCents * $donation->amount / max($totalGrossCents, 1));
                $assignedFeeCents += $feeCents;

                $donation->update([
                    'stripe_invoice_id' => $invoiceId,
                    // Keep the unique payment_intent column on a single row.
                    'stripe_payment_intent_id' => $index === 0
                        ? ($paymentIntentId ?? $donation->stripe_payment_intent_id)
                        : $donation->stripe_payment_intent_id,
                    'metadata' => array_merge($donation->metadata ?? [], ['stripe_invoice_id' => $invoiceId]),
                ]);

                $this->recordSucceededDonation($donation->fresh(), $chargeId, $donation->amount, $feeCents);
            }
        } else {
            $allocations = $subscription->allocations;
            $allocationCount = $allocations->count();
            $assignedFeeCents = 0;

            foreach ($allocations as $index => $allocation) {
                $feeCents = $index === $allocationCount - 1
                    ? $totalFeeCents - $assignedFeeCents
                    : (int) round($totalFeeCents * $allocation->amount_cents / max($totalGrossCents, 1));
                $assignedFeeCents += $feeCents;

                $donation = Donation::create([
                    'transaction_id' => null,
                    'donor_id' => $subscription->donor_id,
                    'campaign_id' => $allocation->campaign_id,
                    'payment_method_id' => PaymentMethod::query()->where('code', 'stripe')->value('id'),
                    'donation_subscription_id' => $subscription->id,
                    'is_general' => $allocation->is_general,
                    'amount' => $allocation->amount_cents,
                    'status' => DonationStatus::Pending,
                    'stripe_status' => StripeStatus::Pending,
                    'stripe_payment_intent_id' => $index === 0 ? $paymentIntentId : null,
                    'stripe_invoice_id' => $invoiceId,
                    'donor_covers_fee' => $subscription->donor_covers_fee,
                    'is_anonymous' => (bool) ($metadata['is_anonymous'] ?? false),
                    'is_recurring' => true,
                    'donor_message' => $metadata['donor_message'] ?? null,
                    'metadata' => ['stripe_invoice_id' => $invoiceId, 'allocation_id' => $allocation->id],
                ]);

                $this->recordSucceededDonation($donation, $chargeId, $allocation->amount_cents, $feeCents);
                $this->submittedNotifier->notify($donation->fresh());
            }
        }

        if ($subscription->status !== DonationSubscriptionStatus::Active) {
            $subscription->update(['status' => DonationSubscriptionStatus::Active]);
        }

        $webhookEvent->update(['processed_at' => now()]);
    }

    private function handleInvoicePaymentFailed(Event $event, WebhookEvent $webhookEvent): void
    {
        /** @var Invoice $invoice */
        $invoice = $event->data->object;

        $subscriptionId = $this->resolveSubscriptionIdFromInvoice($invoice);

        if ($subscriptionId !== null) {
            DonationSubscription::query()
                ->where('stripe_subscription_id', $subscriptionId)
                ->where('status', '!=', DonationSubscriptionStatus::Canceled)
                ->update(['status' => DonationSubscriptionStatus::PastDue]);
        }

        $webhookEvent->update(['processed_at' => now()]);
    }

    private function handleSubscriptionDeleted(Event $event, WebhookEvent $webhookEvent): void
    {
        /** @var Subscription $subscription */
        $subscription = $event->data->object;

        DonationSubscription::query()
            ->where('stripe_subscription_id', $subscription->id)
            ->update(['status' => DonationSubscriptionStatus::Canceled]);

        $webhookEvent->update(['processed_at' => now()]);
    }

    /**
     * Fetches the actual Stripe fee for a one-time charge and records the
     * donation, marking the webhook event processed in the same beat.
     */
    private function finalizeSucceededDonation(Donation $donation, string $chargeId, int $grossCents, WebhookEvent $webhookEvent): void
    {
        $actualFeeCents = $this->gateway->actualFeeFor($chargeId)
            ?? (int) round(((float) config('services.stripe.fee_percent') / 100) * $grossCents);

        $this->recordSucceededDonation($donation, $chargeId, $grossCents, $actualFeeCents);

        if (($donation->metadata['reconstructed_from_webhook'] ?? false) === true) {
            $this->submittedNotifier->notify($donation->fresh());
        }

        $webhookEvent->update(['processed_at' => now()]);
    }

    /**
     * Shared ledger-recording logic for a successfully charged donation,
     * given an already-known fee (whether fetched once for a one-time
     * charge, or apportioned across allocations for a subscription
     * invoice), so it is never re-fetched from Stripe per allocation.
     */
    private function recordSucceededDonation(Donation $donation, string $chargeId, int $grossCents, int $feeCents): void
    {
        $netCents = $grossCents - $feeCents;

        DB::transaction(function () use ($donation, $chargeId, $grossCents, $feeCents, $netCents) {
            $account = $this->resolveStripeAccount();
            $currency = Currency::query()->where('code', 'USD')->firstOrFail();
            $paymentMethod = PaymentMethod::query()->where('code', 'stripe')->first();

            $account = BankAccount::query()->lockForUpdate()->findOrFail($account->id);

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type_id' => TransactionType::idForCode(TransactionType::CODE_DONATION),
                'direction' => TransactionDirection::In,
                'currency_id' => $currency->id,
                'gross_amount' => Money::centsToDecimal($grossCents),
                'fee_amount' => Money::centsToDecimal($feeCents),
                'net_amount' => Money::centsToDecimal($netCents),
                'transaction_date' => now()->toDateString(),
                'reference_number' => $chargeId,
                'description' => $donation->is_general ? 'Online general donation' : 'Online campaign donation',
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
        });

        SendDonationReceipt::dispatch($donation->fresh());
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
            'appeal_id' => ! empty($metadata['appeal_id']) ? (int) $metadata['appeal_id'] : null,
            'payment_method_id' => PaymentMethod::query()->where('code', 'stripe')->value('id'),
            'quantity' => ! empty($metadata['quantity']) ? (int) $metadata['quantity'] : null,
            'unit_amount_cents' => ! empty($metadata['unit_amount_cents']) ? (int) $metadata['unit_amount_cents'] : null,
            'unit_label' => ! empty($metadata['unit_label']) ? $metadata['unit_label'] : null,
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

    private function resolveInvoiceIdFromPaymentIntent(StripeObject $paymentIntent): ?string
    {
        if (($paymentIntent->invoice ?? null) === null) {
            return null;
        }

        return is_string($paymentIntent->invoice)
            ? $paymentIntent->invoice
            : $paymentIntent->invoice->id;
    }

    /**
     * Subscription invoices carry the owning subscription under
     * `parent.subscription_details.subscription` in current Stripe API
     * versions (the top-level `subscription` field has been removed).
     */
    private function resolveSubscriptionIdFromInvoice(Invoice $invoice): ?string
    {
        $subscription = $invoice->parent?->subscription_details?->subscription ?? null;

        if ($subscription === null) {
            return null;
        }

        return is_string($subscription) ? $subscription : $subscription->id;
    }

    private function resolveStripeAccount(): BankAccount
    {
        $configuredId = config('donations.stripe_account_id');

        if ($configuredId) {
            return BankAccount::query()->active()->findOrFail((int) $configuredId);
        }

        return BankAccount::query()
            ->active()
            ->whereHas('currency', fn ($q) => $q->where('code', 'USD'))
            ->orderBy('id')
            ->firstOrFail();
    }

    private function computeRunningBalance(BankAccount $account, TransactionDirection $direction, string $netAmountDecimal): string
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
