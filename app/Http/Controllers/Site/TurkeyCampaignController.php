<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\Turkey\StoreTurkeyRequestRequest;
use App\Http\Requests\Site\Turkey\VerifyTurkeyCodeRequest;
use App\Services\TurkeyCampaignService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TurkeyCampaignController extends Controller
{
    public function __construct(
        private readonly TurkeyCampaignService $turkeyCampaign,
    ) {}

    public function show(): Response
    {
        $this->ensureCampaignEnabled();

        $total = $this->turkeyCampaign->total();
        $title = __('Free Halal Turkey — New Egypt Group');
        $description = __('Only :total free halal turkeys available. Send us a message on WhatsApp for availability and pickup details.', ['total' => $total]);

        $image = url((string) config('turkey_campaign.share_image'));

        return Inertia::render('site/turkey/turkey-show', [
            'remaining' => $this->turkeyCampaign->remaining(),
            'total' => $total,
            'whatsappUrl' => config('turkey_campaign.whatsapp_url'),
        ])->withViewData([
            'meta' => [
                'type' => 'website',
                'title' => $title,
                'description' => $description,
                'image' => $image,
                'image_width' => config('turkey_campaign.share_image_width'),
                'image_height' => config('turkey_campaign.share_image_height'),
                'image_alt' => __('Free Halal Turkey — only :total available', ['total' => $total]),
                'url' => route('turkey.show'),
                'published' => null,
            ],
        ]);
    }

    public function store(StoreTurkeyRequestRequest $request): RedirectResponse
    {
        $this->ensureCampaignEnabled();

        try {
            $this->turkeyCampaign->register($request->safe()->only(['full_name', 'email', 'phone', 'address']));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['full_name' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Thank you! Your request was received. We will contact you on WhatsApp about pickup.'),
        ]);

        return back();
    }

    public function verify(): Response
    {
        $this->ensureCampaignEnabled();

        return Inertia::render('site/turkey/turkey-verify');
    }

    public function verifyStore(VerifyTurkeyCodeRequest $request): RedirectResponse
    {
        $this->ensureCampaignEnabled();

        try {
            $this->turkeyCampaign->verifyDelivery($request->validated('code'));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['code' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Delivery confirmed. Thank you!'),
        ]);

        return back();
    }

    private function ensureCampaignEnabled(): void
    {
        abort_unless($this->turkeyCampaign->isEnabled(), 404);
    }
}
