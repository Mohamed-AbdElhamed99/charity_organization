<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\TransferServiceInterface;
use App\DTOs\CreateTransferDTO;
use App\Enums\TransferRecipientType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Transfer\StoreTransferRequest;
use App\Http\Resources\Admin\Transfer\TransferResource;
use App\Models\BankAccount;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferServiceInterface $transferService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'query',
            'recipient_type',
            'date_from',
            'date_to',
            'campaign_id',
            'page',
            'per_page',
        ]);

        $paginator = $this->transferService->getPaginatedTransfers($filters);

        $transfers = $paginator->toArray();
        $transfers['data'] = TransferResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/transfers/transfers-index', [
            'transfers' => $transfers,
            'campaigns' => Campaign::query()->orderBy('title_en')->get(['id', 'title_ar', 'title_en']),
            'accounts' => BankAccount::query()->active()->orderBy('name')->get(['id', 'name']),
            'search' => $filters,
        ]);
    }

    public function store(StoreTransferRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->transferService->createTransfer(new CreateTransferDTO(
            recipientType: TransferRecipientType::from($validated['recipient_type']),
            recipientName: $validated['recipient_name'],
            amount: (float) $validated['amount'],
            transferDate: $validated['transfer_date'],
            purpose: $validated['purpose'],
            campaignId: isset($validated['campaign_id']) ? (int) $validated['campaign_id'] : null,
            recipientPhone: $validated['recipient_phone'] ?? null,
            beneficiaryId: isset($validated['beneficiary_id']) ? (int) $validated['beneficiary_id'] : null,
            userId: isset($validated['user_id']) ? (int) $validated['user_id'] : null,
            notes: $validated['notes'] ?? null,
            accountId: isset($validated['account_id']) ? (int) $validated['account_id'] : null,
            paymentMethodId: isset($validated['payment_method_id']) ? (int) $validated['payment_method_id'] : null,
            referenceNumber: $validated['reference_number'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transfer recorded successfully.')]);

        return back();
    }
}
