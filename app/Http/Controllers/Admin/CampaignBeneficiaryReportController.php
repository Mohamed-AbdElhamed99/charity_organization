<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CampaignBeneficiaryReportExportMapper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BeneficiarySupport\CampaignBeneficiaryReportRequest;
use App\Models\AidItem;
use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Services\BeneficiaryIdentityVisibilityResolver;
use App\Services\CampaignBeneficiaryReportService;
use App\Services\ReportAccessLogger;
use Inertia\Inertia;
use Inertia\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignBeneficiaryReportController extends Controller
{
    public function __construct(
        private readonly CampaignBeneficiaryReportService $reportService,
        private readonly BeneficiaryIdentityVisibilityResolver $identityResolver,
        private readonly ReportAccessLogger $auditLogger,
    ) {}

    public function show(CampaignBeneficiaryReportRequest $request, Campaign $campaign): Response|StreamedResponse
    {
        $filters = $request->validated();
        $format = $filters['format'] ?? null;

        if ($format !== null) {
            abort_unless($request->user()?->can('export_campaign_beneficiary_reports'), 403);

            return $this->export($request, $campaign, $filters, $format);
        }

        $paginator = $this->reportService->paginate($campaign, $filters);
        $summary = $this->reportService->summarize($campaign, $filters);

        $rows = collect($paginator->items());
        $beneficiaryIds = $rows->pluck('beneficiary_id')->map(fn ($id) => (int) $id)->all();
        $beneficiaries = Beneficiary::query()
            ->whereIn('id', $beneficiaryIds)
            ->with(['individual:id,beneficiary_id,first_name,last_name', 'family:id,beneficiary_id,household_name', 'organization:id,beneficiary_id,name'])
            ->get()
            ->keyBy('id');

        $resolvedRows = $rows->map(function ($row) use ($request, $beneficiaries) {
            $beneficiary = $beneficiaries->get((int) $row->beneficiary_id);
            $canViewIdentity = $beneficiary !== null
                ? $this->identityResolver->canViewIdentity($request->user(), $beneficiary)
                : false;

            return [
                'beneficiary_id' => (int) $row->beneficiary_id,
                'beneficiary_code' => $row->beneficiary_code,
                'beneficiary_type' => $row->beneficiary_type,
                'beneficiary_name' => $canViewIdentity && $beneficiary !== null
                    ? $beneficiary->display_name
                    : $row->beneficiary_code,
                'can_view_identity' => $canViewIdentity,
                'support_events_count' => (int) $row->support_events_count,
                'items_count' => (int) $row->items_count,
                'total_cost' => (int) $row->total_cost,
                'last_supported_at' => $row->last_supported_at,
            ];
        })->values();

        if ($resolvedRows->contains(fn ($row) => $row['can_view_identity'] === true)) {
            $this->auditLogger->log(
                user: $request->user(),
                reportKey: 'campaign_beneficiary_report',
                scopeType: 'campaign',
                scopeId: $campaign->id,
                action: 'view',
                rowCount: $resolvedRows->count(),
                filters: $filters,
            );
        }

        $payload = $paginator->toArray();
        $payload['data'] = $resolvedRows->all();

        return Inertia::render('admin/reports/campaign-beneficiary-report', [
            'campaign' => ['id' => $campaign->id, 'title_ar' => $campaign->title_ar, 'title_en' => $campaign->title_en],
            'rows' => $payload,
            'summary' => [
                'distinct_beneficiaries' => (int) $summary['distinct_beneficiaries'],
                'support_events' => (int) $summary['support_events'],
                'total_items' => (int) $summary['total_items'],
                'total_cost' => (int) $summary['total_cost'],
                'by_aid_item' => $summary['by_aid_item'],
                'by_beneficiary_type' => $summary['by_beneficiary_type'],
                'campaign_expenses_total' => (int) $summary['campaign_expenses_total'],
                'allocated_against_expenses' => (int) $summary['allocated_against_expenses'],
                'unallocated_against_expenses' => (int) $summary['unallocated_against_expenses'],
            ],
            'filters' => $filters,
            'aidItems' => AidItem::query()->active()->orderBy('id')->get(['id', 'name']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function export(
        CampaignBeneficiaryReportRequest $request,
        Campaign $campaign,
        array $filters,
        string $format,
    ): StreamedResponse {
        $rows = $this->reportService->exportRows($campaign, $filters);
        $beneficiaryIds = $rows->pluck('beneficiary_id')->map(fn ($id) => (int) $id)->all();
        $beneficiaries = Beneficiary::query()
            ->whereIn('id', $beneficiaryIds)
            ->with(['individual:id,beneficiary_id,first_name,last_name', 'family:id,beneficiary_id,household_name', 'organization:id,beneficiary_id,name'])
            ->get()
            ->keyBy('id');

        $exportRows = $rows->map(function ($row) use ($request, $beneficiaries) {
            $beneficiary = $beneficiaries->get((int) $row->beneficiary_id);
            $canViewIdentity = $beneficiary !== null
                ? $this->identityResolver->canViewIdentity($request->user(), $beneficiary)
                : false;

            return [
                'beneficiary_code' => $row->beneficiary_code,
                'beneficiary_name' => $canViewIdentity && $beneficiary !== null
                    ? $beneficiary->display_name
                    : $row->beneficiary_code,
                'beneficiary_type' => $row->beneficiary_type,
                'support_events_count' => (int) $row->support_events_count,
                'items_count' => (int) $row->items_count,
                'total_cost' => (int) $row->total_cost,
                'last_supported_at' => $row->last_supported_at,
            ];
        })->values();

        $this->auditLogger->log(
            user: $request->user(),
            reportKey: 'campaign_beneficiary_report',
            scopeType: 'campaign',
            scopeId: $campaign->id,
            action: 'export',
            rowCount: $exportRows->count(),
            filters: $filters,
        );

        $filename = 'campaign-beneficiary-report-'.$campaign->id.'-'.now()->format('Y-m-d').'.'.$format;

        return response()->streamDownload(function () use ($format, $exportRows) {
            if ($format === 'xlsx') {
                $writer = new XlsxWriter;
            } else {
                $writer = new CsvWriter;
            }

            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(CampaignBeneficiaryReportExportMapper::headings()));

            foreach ($exportRows as $row) {
                $writer->addRow(Row::fromValues(CampaignBeneficiaryReportExportMapper::mapRow($row)));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => $format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv',
        ]);
    }
}
