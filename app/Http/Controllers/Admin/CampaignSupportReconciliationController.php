<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CampaignSupportReconciliationController extends Controller
{
    public function show(Request $request, Campaign $campaign): Response
    {
        abort_unless($request->user()?->can('view_beneficiary_reports'), 403);

        $distributedCents = (int) DB::table('beneficiary_support_items as bsi')
            ->join('beneficiary_supports as bs', 'bs.id', '=', 'bsi.beneficiary_support_id')
            ->where('bs.campaign_id', $campaign->id)
            ->sum('bsi.total_cost');

        $campaignExpensesCents = (int) $campaign->expenses()
            ->get()
            ->sum(fn ($expense) => (int) round((float) $expense->amount * 100));

        return Inertia::render('admin/reports/campaign-support-reconciliation', [
            'campaign' => ['id' => $campaign->id, 'title_ar' => $campaign->title_ar, 'title_en' => $campaign->title_en],
            'totals' => [
                'distributed_total' => $distributedCents,
                'campaign_expenses_total' => $campaignExpensesCents,
                'gap' => $campaignExpensesCents - $distributedCents,
            ],
        ]);
    }
}
