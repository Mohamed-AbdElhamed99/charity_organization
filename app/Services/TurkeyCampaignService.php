<?php

namespace App\Services;

use App\Contracts\Services\BeneficiaryServiceInterface;
use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use App\Enums\CampaignStatus;
use App\Enums\SupportStatus;
use App\Enums\TurkeyRequestStatus;
use App\Models\Beneficiary;
use App\Models\BeneficiarySupport;
use App\Models\Campaign;
use App\Models\TurkeyRequest;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Temporary, hardcoded service behind the "Free Halal Turkey" campaign.
 */
class TurkeyCampaignService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 6;

    public function __construct(
        private readonly BeneficiaryServiceInterface $beneficiaryService,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('turkey_campaign.enabled');
    }

    public function total(): int
    {
        return (int) config('turkey_campaign.total');
    }

    /** Turkeys still available: total minus pending and delivered requests. */
    public function remaining(): int
    {
        $taken = TurkeyRequest::query()->holdingStock()->count();

        return max(0, $this->total() - $taken);
    }

    /** Hidden (non-public) campaign every turkey request and support is attached to. */
    public function campaign(): Campaign
    {
        return Campaign::query()->firstOrCreate(
            ['slug' => config('turkey_campaign.campaign_slug')],
            [
                'title_en' => config('turkey_campaign.campaign_title_en'),
                'title_ar' => config('turkey_campaign.campaign_title_ar'),
                'status' => CampaignStatus::Active,
                'is_public' => false,
                'open_donation_form' => false,
                'start_date' => now()->toDateString(),
                'created_by' => $this->creator()->id,
            ],
        );
    }

    /**
     * Register a beneficiary request, atomically reserving one turkey.
     *
     * @param  array{full_name: string, email: string, phone: string, address: string}  $data
     *
     * @throws DomainException when no turkeys are left.
     */
    public function register(array $data): TurkeyRequest
    {
        $campaign = $this->campaign();
        $creator = $this->creator();

        return DB::transaction(function () use ($data, $campaign, $creator): TurkeyRequest {
            Campaign::query()->whereKey($campaign->id)->lockForUpdate()->first();

            if ($this->remaining() <= 0) {
                throw new DomainException(__('All turkeys have been claimed.'));
            }

            [$firstName, $lastName] = $this->splitName($data['full_name']);

            $beneficiary = $this->beneficiaryService->createBeneficiary([
                'type' => BeneficiaryType::Individual->value,
                'status' => BeneficiaryStatus::PendingAssessment->value,
                'notes' => "Free Halal Turkey campaign request. Email: {$data['email']}",
                'individual' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'notes' => "Email: {$data['email']}",
                ],
            ], $creator);

            return TurkeyRequest::query()->create([
                'beneficiary_id' => $beneficiary->id,
                'campaign_id' => $campaign->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'code' => $this->generateCode(),
                'status' => TurkeyRequestStatus::Pending,
            ]);
        });
    }

    /**
     * Confirm delivery using the code handed to the beneficiary.
     *
     * @throws DomainException when the code is unknown or was already used/cancelled.
     */
    public function verifyDelivery(string $code): TurkeyRequest
    {
        $request = TurkeyRequest::query()
            ->where('code', Str::upper(trim($code)))
            ->first();

        if ($request === null) {
            throw new DomainException(__('This code is not valid or was already used.'));
        }

        return $this->markDelivered($request);
    }

    /**
     * @throws DomainException when the request is not pending.
     */
    public function markDelivered(TurkeyRequest $turkeyRequest): TurkeyRequest
    {
        return DB::transaction(function () use ($turkeyRequest): TurkeyRequest {
            $locked = TurkeyRequest::query()->whereKey($turkeyRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== TurkeyRequestStatus::Pending) {
                throw new DomainException(__('This code is not valid or was already used.'));
            }

            $support = BeneficiarySupport::query()->create([
                'beneficiary_id' => $locked->beneficiary_id,
                'campaign_id' => $locked->campaign_id,
                'supported_at' => now()->toDateString(),
                'status' => SupportStatus::Delivered,
                'notes' => 'Free Halal Turkey delivered (code '.$locked->code.')',
                'created_by' => $this->creator()->id,
            ]);

            $locked->update([
                'status' => TurkeyRequestStatus::Delivered,
                'delivered_at' => now(),
                'beneficiary_support_id' => $support->id,
            ]);

            Beneficiary::query()
                ->whereKey($locked->beneficiary_id)
                ->update(['status' => BeneficiaryStatus::Active]);

            return $locked->refresh();
        });
    }

    /**
     * Cancel a pending request, returning its turkey to stock.
     *
     * @throws DomainException when the request is not pending.
     */
    public function cancel(TurkeyRequest $turkeyRequest): TurkeyRequest
    {
        return DB::transaction(function () use ($turkeyRequest): TurkeyRequest {
            $locked = TurkeyRequest::query()->whereKey($turkeyRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== TurkeyRequestStatus::Pending) {
                throw new DomainException(__('Only pending requests can be cancelled.'));
            }

            $locked->update(['status' => TurkeyRequestStatus::Cancelled]);

            return $locked->refresh();
        });
    }

    private function generateCode(): string
    {
        do {
            $suffix = '';

            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $suffix .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }

            $code = config('turkey_campaign.code_prefix').$suffix;
        } while (TurkeyRequest::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2) ?: [];
        $firstName = $parts[0] ?? $fullName;
        $lastName = $parts[1] ?? '-';

        return [$firstName, $lastName];
    }

    private function creator(): User
    {
        $configured = config('turkey_campaign.created_by');

        $user = $configured !== null && $configured !== ''
            ? User::query()->find($configured)
            : null;

        $user ??= User::query()->role('super_admin')->orderBy('id')->first();
        $user ??= User::query()->orderBy('id')->first();

        return $user ?? throw new RuntimeException('No staff user available to attribute the turkey campaign records to.');
    }
}
