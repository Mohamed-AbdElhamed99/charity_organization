<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BeneficiarySupportReportExportMapper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BeneficiarySupport\BeneficiarySupportReportRequest;
use App\Models\AidItem;
use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Services\BeneficiaryIdentityVisibilityResolver;
use App\Services\BeneficiarySupportReportService;
use App\Services\ReportAccessLogger;
use Inertia\Inertia;
use Inertia\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BeneficiarySupportReportController extends Controller
{
    public function __construct(
        private readonly BeneficiarySupportReportService $reportService,
        private readonly BeneficiaryIdentityVisibilityResolver $identityResolver,
        private readonly ReportAccessLogger $auditLogger,
    ) {}

    public function show(BeneficiarySupportReportRequest $request, Beneficiary $beneficiary): Response|StreamedResponse
    {
        abort_unless($this->identityResolver->canViewIdentity($request->user(), $beneficiary), 403);

        $filters = $request->validated();
        $format = $filters['format'] ?? null;

        if ($format !== null) {
            abort_unless($request->user()?->can('export_beneficiary_support_reports'), 403);

            return $this->export($request, $beneficiary, $filters, $format);
        }

        $rows = $this->reportService->query($beneficiary, $filters);
        $grouped = $this->reportService->groupByCampaign($rows);
        $totals = $this->reportService->totals($rows);

        $this->auditLogger->log(
            user: $request->user(),
            reportKey: 'beneficiary_support_report',
            scopeType: 'beneficiary',
            scopeId: $beneficiary->id,
            action: 'view',
            rowCount: $rows->count(),
            filters: $filters,
        );

        return Inertia::render('admin/reports/beneficiary-support-report', [
            'beneficiary' => [
                'id' => $beneficiary->id,
                'code' => $beneficiary->code,
                'display_name' => $beneficiary->display_name,
                'type' => $beneficiary->type?->value,
            ],
            'grouped' => $grouped,
            'totals' => $totals,
            'filters' => $filters,
            'campaigns' => Campaign::query()->orderBy('title_en')->get(['id', 'title_en', 'title_ar']),
            'aidItems' => AidItem::query()->active()->orderBy('id')->get(['id', 'name']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function export(
        BeneficiarySupportReportRequest $request,
        Beneficiary $beneficiary,
        array $filters,
        string $format,
    ): StreamedResponse {
        $rows = $this->reportService->query($beneficiary, $filters)->map(fn ($row) => [
            'campaign_id' => (int) $row->campaign_id,
            'campaign_title_en' => $row->campaign_title_en,
            'campaign_title_ar' => $row->campaign_title_ar,
            'supported_at' => $row->supported_at,
            'status' => $row->status,
            'item_name_snapshot' => $row->item_name_snapshot,
            'quantity' => (int) $row->quantity,
            'unit_cost' => (int) $row->unit_cost,
            'total_cost' => (int) $row->total_cost,
            'campaign_expense_id' => $row->campaign_expense_id !== null ? (int) $row->campaign_expense_id : null,
        ]);

        $this->auditLogger->log(
            user: $request->user(),
            reportKey: 'beneficiary_support_report',
            scopeType: 'beneficiary',
            scopeId: $beneficiary->id,
            action: 'export',
            rowCount: $rows->count(),
            filters: $filters,
        );

        $filename = 'beneficiary-support-report-'.$beneficiary->id.'-'.now()->format('Y-m-d').'.'.$format;

        return response()->streamDownload(function () use ($format, $rows) {
            if ($format === 'xlsx') {
                $writer = new XlsxWriter;
            } else {
                $writer = new CsvWriter;
            }

            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(BeneficiarySupportReportExportMapper::headings()));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(BeneficiarySupportReportExportMapper::mapLine($row)));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => $format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv',
        ]);
    }
}
