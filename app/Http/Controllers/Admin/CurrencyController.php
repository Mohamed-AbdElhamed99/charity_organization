<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\CurrencyServiceInterface;
use App\DTOs\CreateCurrencyDTO;
use App\DTOs\UpdateCurrencyDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Currency\StoreCurrencyRequest;
use App\Http\Requests\Admin\Currency\UpdateCurrencyRequest;
use App\Http\Resources\Admin\Currency\CurrencyResource;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyServiceInterface $currencyService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'status', 'page', 'per_page']);
        $paginator = $this->currencyService->getPaginatedCurrencies($filters);

        $currencies = $paginator->toArray();
        $currencies['data'] = CurrencyResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/currencies/currencies-index', [
            'currencies' => $currencies,
            'search' => $filters,
        ]);
    }

    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $currency = $this->currencyService->createCurrency(new CreateCurrencyDTO(
            code: $validated['code'],
            name: $validated['name'],
            symbol: $validated['symbol'] ?? null,
            isActive: (bool) $validated['is_active'],
            isDefault: (bool) $validated['is_default'],
        ));

        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => __('Currency created successfully.')],
            'currency' => (new CurrencyResource($currency))->resolve(),
        ]);

        return back();
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $validated = $request->validated();

        $currency = $this->currencyService->updateCurrency($currency, new UpdateCurrencyDTO(
            code: $validated['code'],
            name: $validated['name'],
            symbol: $validated['symbol'] ?? null,
            isActive: (bool) $validated['is_active'],
            isDefault: (bool) $validated['is_default'],
        ));

        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => __('Currency updated successfully.')],
            'currency' => (new CurrencyResource($currency))->resolve(),
        ]);

        return back();
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        try {
            $this->currencyService->deleteCurrency($currency);
        } catch (RuntimeException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Currency deleted successfully.'),
        ]);

        return back();
    }
}
