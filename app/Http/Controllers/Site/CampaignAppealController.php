<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\Site\CampaignAppealResource;
use App\Models\CampaignAppeal;
use App\Models\Country;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampaignAppealController extends Controller
{
    public function show(CampaignAppeal $appeal): Response
    {

        $appeal->load(['campaign.media', 'media']);

        abort_unless($appeal->isDonatable(), 404);

        $appealData = (new CampaignAppealResource($appeal))->resolve();
        $description = trim(strip_tags((string) $appealData['subtitle']));

        if ($description === '') {
            $description = trim(strip_tags((string) $appealData['title']));
        }

        $image = $appealData['image'] ?: '/images/new-egypt-logo.png';

        if (! str_starts_with($image, 'http://') && ! str_starts_with($image, 'https://')) {
            $image = url($image);
        }

        return Inertia::render('site/appeals/appeal-show', [
            'appeal' => $appealData,
            'minAmountCents' => (int) config('donations.min_amount_cents', 100),
            'publishableKey' => config('services.stripe.key'),
            'feeConfig' => [
                'percent' => (float) config('services.stripe.fee_percent', 2.9),
                'fixedCents' => (int) config('services.stripe.fee_fixed_cents', 30),
                'currency' => 'USD',
            ],
            'countries' => Country::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Country $country) => [
                    'id' => $country->id,
                    'name' => $country->name,
                ])
                ->all(),
            'organization' => [
                'legalName' => config('donations.organization.legal_name'),
                'taxStatus' => config('donations.organization.tax_status'),
                'ein' => config('donations.organization.ein'),
                'website' => config('donations.organization.website'),
            ],
            'zelle' => [
                'qrUrl' => url((string) config('donations.zelle.qr')),
                'email' => filled(config('donations.zelle.email')) ? (string) config('donations.zelle.email') : null,
            ],
        ])->withViewData([
            'meta' => [
                'type' => 'website',
                'title' => $appealData['title'],
                'description' => Str::limit($description, 160),
                'image' => $image,
                'url' => route('appeals.show', ['appeal' => $appeal->slug]),
                'published' => null,
            ],
        ]);
    }
}
