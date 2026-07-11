<?php

namespace App\Http\Controllers\Site;

use App\DTOs\CreateDonationIntentDTO;
use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\Donation\StoreDonationIntentRequest;
use App\Http\Resources\Site\CampaignResource;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Country;
use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DonationController extends Controller
{
    public function __construct(
        private readonly DonationService $donationService,
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

    public function thankYou(string $paymentIntentId): Response
    {
        $donation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->with(['campaign', 'donor'])
            ->first();

        return Inertia::render('site/donations/thank-you', [
            'paymentIntentId' => $paymentIntentId,
            'donation' => $donation ? [
                'status' => $donation->status?->value,
                'amount_cents' => $donation->amount,
                'campaign_title' => $donation->is_general ? null : $donation->campaign?->title,
                'is_general' => $donation->is_general,
                'email' => $donation->donor?->email,
            ] : null,
        ]);
    }

    public function status(Request $request, string $paymentIntentId): JsonResponse
    {
        $donation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->with('donor')
            ->first();

        if ($donation === null) {
            return response()->json(['status' => 'unknown']);
        }

        return response()->json([
            'status' => $donation->status?->value,
            'amount_cents' => $donation->amount,
            'email' => $donation->donor?->email,
        ]);
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