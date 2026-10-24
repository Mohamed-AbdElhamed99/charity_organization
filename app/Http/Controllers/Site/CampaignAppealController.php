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

        $appealData = (new CampaignAppealResource($appeal))->resolve();

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
        ])->withViewData([
            'meta' => [
                'type' => 'website',
                'title' => $appealData['title'],
                'description' => Str::limit(strip_tags((string) $appealData['subtitle']), 160),
                'image' => $appealData['image'] ?: url('/images/new-egypt-logo.png'),
                'url' => route('appeals.show', ['appeal' => $appeal->slug]),
                'published' => null,
            ],
        ]);
    }
}
