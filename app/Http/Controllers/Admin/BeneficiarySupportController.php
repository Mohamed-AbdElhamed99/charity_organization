<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BeneficiarySupport\StoreBeneficiarySupportRequest;
use App\Models\AidItem;
use App\Models\Beneficiary;
use App\Models\BeneficiarySupport;
use App\Models\Campaign;
use App\Models\CampaignExpense;
use App\Services\BeneficiaryIdentityVisibilityResolver;
use App\Services\BeneficiarySupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BeneficiarySupportController extends Controller
{
    public function __construct(
        private readonly BeneficiarySupportService $supportService,
        private readonly BeneficiaryIdentityVisibilityResolver $identityResolver,
    ) {}

    public function createFromCampaign(Request $request, Campaign $campaign): Response
    {
        abort_unless($request->user()?->can('create', BeneficiarySupport::class), 403);

        $beneficiaries = Beneficiary::query()
            ->active()
            ->with(['individual:id,beneficiary_id,first_name,last_name', 'family:id,beneficiary_id,household_name', 'organization:id,beneficiary_id,name'])
            ->orderBy('code')
            ->get()
            ->map(fn (Beneficiary $beneficiary) => [
                'id' => $beneficiary->id,
                'code' => $beneficiary->code,
                'display_name' => $this->identityResolver->displayIdentity($request->user(), $beneficiary),
                'can_view_identity' => $this->identityResolver->canViewIdentity($request->user(), $beneficiary),
                'type' => $beneficiary->type?->value,
            ])
            ->values();

        return Inertia::render('admin/beneficiary-supports/create', [
            'mode' => 'campaign',
            'campaign' => ['id' => $campaign->id, 'title_ar' => $campaign->title_ar, 'title_en' => $campaign->title_en],
            'beneficiary' => null,
            'beneficiaries' => $beneficiaries,
            'campaigns' => collect([['id' => $campaign->id, 'title_ar' => $campaign->title_ar, 'title_en' => $campaign->title_en]]),
            'aidItems' => AidItem::query()->active()->orderBy('id')->get(['id', 'name', 'unit', 'default_unit_cost']),
            'campaignExpenses' => CampaignExpense::query()
                ->where('campaign_id', $campaign->id)
                ->orderByDesc('expense_date')
                ->get(['id', 'campaign_id', 'expense_date', 'amount']),
        ]);
    }

    public function createFromBeneficiary(Request $request, Beneficiary $beneficiary): Response
    {
        abort_unless($request->user()?->can('create', BeneficiarySupport::class), 403);

        return Inertia::render('admin/beneficiary-supports/create', [
            'mode' => 'beneficiary',
            'beneficiary' => [
                'id' => $beneficiary->id,
                'code' => $beneficiary->code,
                'display_name' => $this->identityResolver->displayIdentity($request->user(), $beneficiary),
                'can_view_identity' => $this->identityResolver->canViewIdentity($request->user(), $beneficiary),
                'type' => $beneficiary->type?->value,
            ],
            'campaign' => null,
            'beneficiaries' => collect([[
                'id' => $beneficiary->id,
                'code' => $beneficiary->code,
                'display_name' => $this->identityResolver->displayIdentity($request->user(), $beneficiary),
                'can_view_identity' => $this->identityResolver->canViewIdentity($request->user(), $beneficiary),
                'type' => $beneficiary->type?->value,
            ]]),
            'campaigns' => Campaign::query()
                ->active()
                ->orderBy('title_en')
                ->get(['id', 'title_ar', 'title_en']),
            'aidItems' => AidItem::query()->active()->orderBy('id')->get(['id', 'name', 'unit', 'default_unit_cost']),
            'campaignExpenses' => CampaignExpense::query()
                ->orderByDesc('expense_date')
                ->get(['id', 'campaign_id', 'expense_date', 'amount']),
        ]);
    }

    public function store(StoreBeneficiarySupportRequest $request): RedirectResponse
    {
        $support = $this->supportService->createSupport($request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Beneficiary support recorded successfully.')]);

        return redirect()->route('admin.campaigns.beneficiary-report', $support->campaign_id);
    }
}
