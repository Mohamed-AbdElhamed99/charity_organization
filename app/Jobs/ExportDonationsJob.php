<?php

namespace App\Jobs;

use App\Exports\DonationExportMapper;
use App\Models\User;
use App\Services\DonationReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class ExportDonationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public User $user,
        public array $filters,
        public string $format = 'csv',
    ) {}

    public function handle(DonationReportService $reportService): void
    {
        $rows = $reportService->getExportRows($this->filters);
        $filename = 'donations-'.now()->format('Y-m-d-His').'.'.$this->format;
        $path = storage_path('app/exports/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        if ($this->format === 'xlsx') {
            $writer = new XlsxWriter;
        } else {
            $writer = new CsvWriter;
        }

        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(DonationExportMapper::headings()));

        foreach ($rows as $donation) {
            $writer->addRow(Row::fromValues(DonationExportMapper::mapRow($donation)));
        }

        $writer->close();

        Mail::raw(
            __('Your donation export is ready at: :path', ['path' => $path]),
            fn ($message) => $message->to($this->user->email)
                ->subject(__('Donation export ready'))
        );
    }
}
