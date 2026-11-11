<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TurkeyRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\TurkeyRequest;
use App\Services\TurkeyCampaignService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TurkeyRequestController extends Controller
{
    public function __construct(
        private readonly TurkeyCampaignService $turkeyCampaign,
    ) {}

    public function index(): Response
    {
        $requests = TurkeyRequest::query()
            ->latest()
            ->get()
            ->map(fn (TurkeyRequest $request) => [
                'id' => $request->id,
                'beneficiary_id' => $request->beneficiary_id,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'code' => $request->code,
                'status' => $request->status->value,
                'created_at' => $request->created_at?->toIso8601String(),
                'delivered_at' => $request->delivered_at?->toIso8601String(),
            ])
            ->all();

        return Inertia::render('admin/turkey-requests/turkey-requests-index', [
            'requests' => $requests,
            'stats' => [
                'total' => $this->turkeyCampaign->total(),
                'remaining' => $this->turkeyCampaign->remaining(),
                'pending' => TurkeyRequest::query()->where('status', TurkeyRequestStatus::Pending)->count(),
                'delivered' => TurkeyRequest::query()->where('status', TurkeyRequestStatus::Delivered)->count(),
            ],
        ]);
    }

    public function markDelivered(TurkeyRequest $turkeyRequest): RedirectResponse
    {
        return $this->respondTo(
            fn () => $this->turkeyCampaign->markDelivered($turkeyRequest),
            __('Request marked as delivered.'),
        );
    }

    public function cancel(TurkeyRequest $turkeyRequest): RedirectResponse
    {
        return $this->respondTo(
            fn () => $this->turkeyCampaign->cancel($turkeyRequest),
            __('Request cancelled. The turkey is available again.'),
        );
    }

    private function respondTo(callable $action, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $successMessage]);

        return back();
    }
}
