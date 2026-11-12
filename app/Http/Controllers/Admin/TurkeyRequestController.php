<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TurkeyRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TurkeyRequest\UpdateTurkeyRequestRequest;
use App\Models\TurkeyRequest;
use App\Services\TurkeyCampaignService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
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
                'quantity' => $request->quantity,
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
                'pending' => (int) TurkeyRequest::query()->where('status', TurkeyRequestStatus::Pending)->sum('quantity'),
                'delivered' => (int) TurkeyRequest::query()->where('status', TurkeyRequestStatus::Delivered)->sum('quantity'),
            ],
        ]);
    }

    public function update(UpdateTurkeyRequestRequest $request, TurkeyRequest $turkeyRequest): RedirectResponse
    {
        try {
            $this->turkeyCampaign->update($turkeyRequest, $request->validated());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request updated.')]);

        return back();
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
