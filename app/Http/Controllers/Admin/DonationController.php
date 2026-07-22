<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DonationExportMapper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Donation\DonationFilterRequest;
use App\Http\Resources\Admin\Donation\DonationListResource;
use App\Jobs\ExportDonationsJob;
use App\Models\Campaign;
use App\Models\Currency;
use App\Services\DonationReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DonationController extends Controller
{
    public function __construct(
        private readonly DonationReportService $reportService,
    ) {}

    public function index(DonationFilterRequest $request): Response
    {
        $filters = $request->validated();
        $paginator = $this->reportService->paginate($filters);
        $summary = $this->reportService->summarize($filters);

        $donations = $paginator->toArray();
        $donations['data'] = DonationListResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/donations/donations-index', [
            'donations' => $donations,
            'summary' => [
                'donation_count' => $summary['donation_count'],
                'total_gift_cents' => $summary['total_gift_cents'],
                'total_gross_cents' => $summary['total_gross_cents'],
                'total_fee_cents' => $summary['total_fee_cents'],
                'total_net_cents' => $summary['total_net_cents'],
                'by_campaign' => $summary['by_campaign']->map(fn ($row) => [
                    'campaign_id' => $row->campaign_id,
                    'campaign_title' => $row->campaign?->title ?? __('General'),
                    'count' => (int) $row->count,
                    'total_gift_cents' => (int) $row->total_gift_cents,
                ])->values(),
                'by_month' => $summary['by_month']->map(fn ($row) => [
                    'month' => $row->month,
                    'count' => (int) $row->count,
                    'total_gift_cents' => (int) $row->total_gift_cents,
                ])->values(),
            ],
            'campaigns' => Campaign::query()->orderBy('title_en')->get(['id', 'title_en', 'title_ar']),
            'currencies' => Currency::query()->active()->orderBy('code')->get(['id', 'code', 'symbol']),
            'search' => $filters,
        ]);
    }

    public function export(DonationFilterRequest $request): StreamedResponse|RedirectResponse
    {
        $filters = $request->validated();
        $format = $filters['format'] ?? 'csv';
        $count = $this->reportService->countForExport($filters);
        $maxSync = config('donations.export_sync_max_rows', 5000);

        if ($count > $maxSync) {
            ExportDonationsJob::dispatch(Auth::user(), $filters, $format);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Your export is being prepared. You will receive a notification when it is ready.'),
            ]);

            return back();
        }

        $rows = $this->reportService->getExportRows($filters);
        $filename = 'donations-'.now()->format('Y-m-d').'.'.$format;

        return response()->streamDownload(function () use ($rows, $format) {
            if ($format === 'xlsx') {
                $writer = new XlsxWriter;
                $writer->openToFile('php://output');

                $writer->addRow(Row::fromValues(DonationExportMapper::headings()));

                foreach ($rows as $donation) {
                    $writer->addRow(Row::fromValues(DonationExportMapper::mapRow($donation)));
                }

                $writer->close();

                return;
            }

            $writer = new CsvWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(DonationExportMapper::headings()));

            foreach ($rows as $donation) {
                $writer->addRow(Row::fromValues(DonationExportMapper::mapRow($donation)));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => $format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv',
        ]);
    }
}
