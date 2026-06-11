<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\PaymentMethodServiceInterface;
use App\DTOs\CreatePaymentMethodDTO;
use App\DTOs\UpdatePaymentMethodDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentMethod\BulkDestroyPaymentMethodRequest;
use App\Http\Requests\Admin\PaymentMethod\RestorePaymentMethodRequest;
use App\Http\Requests\Admin\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\Admin\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Resources\Admin\PaymentMethod\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly PaymentMethodServiceInterface $paymentMethodService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'status', 'page', 'per_page']);
        $paginator = $this->paymentMethodService->getPaginatedPaymentMethods($filters);

        $paymentMethods = $paginator->toArray();
        $paymentMethods['data'] = PaymentMethodResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/payment-methods/payment-methods-index', [
            'paymentMethods' => $paymentMethods,
            'search' => $filters,
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->paymentMethodService->createPaymentMethod(new CreatePaymentMethodDTO(
            name: $validated['name'],
            code: $validated['code'],
            isActive: (bool) $validated['is_active'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment method created successfully.')]);

        return back();
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $request->validated();

        $this->paymentMethodService->updatePaymentMethod($paymentMethod, new UpdatePaymentMethodDTO(
            name: $validated['name'],
            code: $validated['code'],
            isActive: (bool) $validated['is_active'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment method updated successfully.')]);

        return back();
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $result = $this->paymentMethodService->deletePaymentMethod($paymentMethod);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result === 'deactivated'
                ? __('Payment method is in use and was deactivated instead of deleted.')
                : __('Payment method deleted successfully.'),
        ]);

        return back();
    }

    public function bulkDestroy(BulkDestroyPaymentMethodRequest $request): RedirectResponse
    {
        $result = $this->paymentMethodService->bulkDelete($request->validated('ids'));

        $message = match (true) {
            $result['deleted'] > 0 && $result['deactivated'] > 0 => __(':deleted payment method(s) deleted and :deactivated deactivated because they are in use.', [
                'deleted' => $result['deleted'],
                'deactivated' => $result['deactivated'],
            ]),
            $result['deactivated'] > 0 => __(':count payment method(s) deactivated because they are in use.', [
                'count' => $result['deactivated'],
            ]),
            default => __('Payment methods deleted successfully.'),
        };

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function restore(RestorePaymentMethodRequest $request, int|string $id): RedirectResponse
    {
        $this->paymentMethodService->restorePaymentMethod($id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment method restored successfully.')]);

        return back();
    }
}
