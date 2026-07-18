<?php

namespace App\Http\Controllers\Site;

use App\Contracts\PaymentGateway;
use App\DTOs\CreateDonationIntentDTO;
use App\DTOs\CreateDonationSubscriptionDTO;
use App\DTOs\DonationAllocationInput;
use App\Enums\CampaignStatus;
use App\Enums\RecurrenceFrequency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\Donation\StoreDonationIntentRequest;
use App\Http\Requests\Site\Donation\StoreDonationSubscriptionRequest;
use App\Http\Resources\Site\CampaignResource;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Country;
use App\Models\Donation;
use App\Models\DonationSubscription;
use App\Services\DonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DonationController extends Controller
{
    public function __construct(
        private readonly DonationService $donationService,
        private readonly PaymentGateway $gateway,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'category' => $request->query('category'),
            'search' => $request->query('search'),
            'page' => $request->query('page'),
        ];

        $paginator = Campaign::query()
            ->public()
            ->withOpenDonation()
            ->with(['category', 'media'])
            ->when($filters['search'], function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $q->where('title_ar', 'like', "%{$value}%")
                        ->orWhere('title_en', 'like', "%{$value}%");
                });
            })
            ->when($filters['category'], function ($query, $value) {
                $query->where('category_id', $value);
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $campaigns = $paginator->toArray();
        $campaigns['data'] = CampaignResource::collection($paginator->items())->resolve();

        $categories = CampaignCategory::query()
            ->active()
            ->orderBy('name_en')
            ->get(['id', 'name_ar', 'name_en'])
            ->map(fn (CampaignCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => (string) $category->id,
                'count' => Campaign::query()
                    ->public()
                    ->withOpenDonation()
                    ->where('category_id', $category->id)
                    ->count(),
            ])
            ->values()
            ->all();

        return Inertia::render('site/donations/donations-index', [
            'campaigns' => $campaigns,
            'categories' => $categories,
            'filters' => $filters,
            'feeConfig' => $this->feeConfig(),
        ])->withViewData([
            'meta' => [
                'type' => 'website',
                'title' => __('Donations'),
                'description' => __('Support our campaigns with a donation to New Egypt Group.'),
                'image' => url('/images/new-egypt-logo.png'),
                'url' => route('donations.index'),
                'published' => null,
            ],
        ]);
    }

    public function generalDonate(): Response
    {
        return Inertia::render('site/donations/donate', [
            'campaign' => null,
            'isGeneral' => true,
            'minAmountCents' => config('donations.min_amount_cents', 100),
            'stripePublishableKey' => config('services.stripe.key'),
            'publishableKey' => config('services.stripe.key'),
            'feeConfig' => $this->feeConfig(),
            'countries' => $this->countriesList(),
        ])->withViewData([
            'meta' => [
                'type' => 'website',
                'title' => __('General Donation'),
                'description' => __('Support New Egypt Group with a general donation.'),
                'image' => url('/images/new-egypt-logo.png'),
                'url' => route('donate.general'),
                'published' => null,
            ],
        ]);
    }

    public function campaignDonate(Campaign $campaign): Response
    {
        abort_if(
            ! $campaign->is_public
            || ! $campaign->open_donation_form
            || $campaign->status !== CampaignStatus::Active,
            404
        );

        $campaign->load(['category', 'media']);
        $campaignData = (new CampaignResource($campaign))->resolve();

        $image = $campaignData['thumbnail']
            ?: (str_starts_with((string) $campaignData['main_media_type'], 'image/')
                ? $campaignData['main_media']
                : '');

        return Inertia::render('site/donations/donate', [
            'campaign' => $campaignData,
            'isGeneral' => false,
            'minAmountCents' => config('donations.min_amount_cents', 100),
            'stripePublishableKey' => config('services.stripe.key'),
            'publishableKey' => config('services.stripe.key'),
            'feeConfig' => $this->feeConfig(),
            'countries' => $this->countriesList(),
        ])->withViewData([
            'meta' => [
                'type' => 'article',
                'title' => __('Donate to :campaign', ['campaign' => $campaignData['title']]),
                'description' => Str::limit(strip_tags((string) $campaignData['excerpt']), 160),
                'image' => $image ?: url('/images/new-egypt-logo.png'),
                'url' => route('campaigns.donate', $campaign),
                'published' => $campaignData['published_at'] ?? null,
            ],
        ]);
    }

    public function storeIntent(StoreDonationIntentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->donationService->createIntent(new CreateDonationIntentDTO(
            campaignId: isset($validated['campaign_id']) ? (int) $validated['campaign_id'] : null,
            isGeneral: (bool) $validated['is_general'],
            amountCents: (int) $validated['amount'],
            donorCoversFee: (bool) $validated['donor_covers_fee'],
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
            countryId: isset($validated['country_id']) ? (int) $validated['country_id'] : null,
            isAnonymous: (bool) ($validated['is_anonymous'] ?? false),
            donorMessage: $validated['donor_message'] ?? null,
        ));

        return response()->json($result);
    }

    public function storeSubscription(StoreDonationSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $allocations = array_map(
            fn (array $allocation) => new DonationAllocationInput(
                campaignId: isset($allocation['campaign_id']) ? (int) $allocation['campaign_id'] : null,
                isGeneral: (bool) ($allocation['is_general'] ?? false),
                amountCents: (int) $allocation['amount'],
            ),
            $validated['allocations'],
        );

        $result = $this->donationService->createSubscriptionIntent(new CreateDonationSubscriptionDTO(
            frequency: RecurrenceFrequency::from($validated['frequency']),
            allocations: $allocations,
            donorCoversFee: (bool) $validated['donor_covers_fee'],
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
            countryId: isset($validated['country_id']) ? (int) $validated['country_id'] : null,
            isAnonymous: (bool) ($validated['is_anonymous'] ?? false),
            donorMessage: $validated['donor_message'] ?? null,
        ));

        return response()->json($result);
    }

    /**
     * Active, public, open-for-donation campaigns for the recurring donation
     * allocation picker.
     */
    public function donatableCampaignsList(): JsonResponse
    {
        $campaigns = Campaign::query()
            ->public()
            ->withOpenDonation()
            ->orderBy('title_en')
            ->get(['id', 'title_ar', 'title_en'])
            ->map(fn (Campaign $campaign) => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ])
            ->values();

        return response()->json($campaigns);
    }

    public function thankYou(string $paymentIntentId): Response
    {
        $donation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->with(['campaign', 'donor', 'donationSubscription'])
            ->first();

        return Inertia::render('site/donations/thank-you', [
            'paymentIntentId' => $paymentIntentId,
            'donation' => $donation ? $this->donationSnapshot($donation) : null,
        ]);
    }

    public function status(Request $request, string $paymentIntentId): JsonResponse
    {
        $donation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->with(['donor', 'donationSubscription'])
            ->first();

        if ($donation === null) {
            return response()->json(['status' => 'unknown']);
        }

        return response()->json($this->donationSnapshot($donation));
    }

    public function subscriptionPortal(string $stripeSubscriptionId): RedirectResponse
    {
        $subscription = DonationSubscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->firstOrFail();

        $url = $this->gateway->createBillingPortalSession(
            $subscription->stripe_customer_id,
            route('donations.index'),
        );

        return redirect()->away($url);
    }

    /**
     * @return array<string, mixed>
     */
    private function donationSnapshot(Donation $donation): array
    {
        return [
            'status' => $donation->status?->value,
            'amount_cents' => $donation->amount,
            'campaign_title' => $donation->is_general ? null : $donation->campaign?->title,
            'is_general' => $donation->is_general,
            'email' => $donation->donor?->email,
            'is_recurring' => $donation->is_recurring,
            'manage_subscription_url' => $donation->is_recurring && $donation->donationSubscription
                ? route('donations.subscriptions.portal', $donation->donationSubscription->stripe_subscription_id)
                : null,
        ];
    }

    /**
     * @return array{percent: float, fixedCents: int, currency: string}
     */
    private function feeConfig(): array
    {
        return [
            'percent' => (float) config('services.stripe.fee_percent', 2.9),
            'fixedCents' => (int) config('services.stripe.fee_fixed_cents', 30),
            'currency' => 'USD',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function countriesList(): array
    {
        return Country::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Country $country) => [
                'id' => $country->id,
                'name' => $country->name,
            ])
            ->all();
    }
}