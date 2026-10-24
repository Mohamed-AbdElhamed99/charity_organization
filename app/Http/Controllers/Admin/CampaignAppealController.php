<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CampaignAppeal\StoreCampaignAppealRequest;
use App\Http\Requests\Admin\CampaignAppeal\UpdateCampaignAppealRequest;
use App\Http\Resources\Admin\CampaignAppeal\CampaignAppealResource;
use App\Models\Campaign;
use App\Models\CampaignAppeal;
use App\Services\CampaignAppealService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CampaignAppealController extends Controller
{
    public function __construct(
        private readonly CampaignAppealService $campaignAppealService,
    ) {}

    public function index(Campaign $campaign): Response
    {
        $appeals = $campaign->appeals()->ordered()->with('media')->get();

        return Inertia::render('admin/campaigns/appeals/appeals-index', [
            'campaign' => $this->campaignSummary($campaign),
            'appeals' => CampaignAppealResource::collection($appeals)->resolve(),
        ]);
    }

    public function create(Campaign $campaign): Response
    {
        return Inertia::render('admin/campaigns/appeals/appeals-create', [
            'campaign' => $this->campaignSummary($campaign),
        ]);
    }

    public function store(StoreCampaignAppealRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->campaignAppealService->create(
            $campaign,
            $request->validated(),
            $request->file('hero'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign appeal created.')]);

        return redirect()->route('admin.campaigns.appeals.index', $campaign);
    }

    public function edit(Campaign $campaign, CampaignAppeal $appeal): Response
    {
        $this->ensureAppealBelongsToCampaign($campaign, $appeal);
        $appeal->load('media');

        return Inertia::render('admin/campaigns/appeals/appeals-edit', [
            'campaign' => $this->campaignSummary($campaign),
            'appeal' => (new CampaignAppealResource($appeal))->resolve(),
        ]);
    }

    public function update(UpdateCampaignAppealRequest $request, Campaign $campaign, CampaignAppeal $appeal): RedirectResponse
    {
        $this->ensureAppealBelongsToCampaign($campaign, $appeal);

        $this->campaignAppealService->update(
            $appeal,
            $request->validated(),
            $request->file('hero'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign appeal updated.')]);

        return redirect()->route('admin.campaigns.appeals.index', $campaign);
    }

    public function destroy(Campaign $campaign, CampaignAppeal $appeal): RedirectResponse
    {
        $this->ensureAppealBelongsToCampaign($campaign, $appeal);

        $appeal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign appeal removed.')]);

        return redirect()->route('admin.campaigns.appeals.index', $campaign);
    }

    /**
     * @return array{id: int, title_en: string, title_ar: string}
     */
    private function campaignSummary(Campaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'title_en' => $campaign->title_en,
            'title_ar' => $campaign->title_ar,
        ];
    }

    private function ensureAppealBelongsToCampaign(Campaign $campaign, CampaignAppeal $appeal): void
    {
        abort_unless($appeal->campaign_id === $campaign->id, 404);
    }
}
