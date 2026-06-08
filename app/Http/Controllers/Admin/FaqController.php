<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\FaqServiceInterface;
use App\DTOs\CreateFaqDTO;
use App\DTOs\UpdateFaqDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\BulkDestroyFaqRequest;
use App\Http\Requests\Admin\Faq\RestoreFaqRequest;
use App\Http\Requests\Admin\Faq\StoreFaqRequest;
use App\Http\Requests\Admin\Faq\UpdateFaqRequest;
use App\Http\Resources\Admin\Faq\FaqResource;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function __construct(
        private readonly FaqServiceInterface $faqService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'status', 'page', 'per_page']);
        $paginator = $this->faqService->getPaginatedFaqs($filters);

        $faqs = $paginator->toArray();
        $faqs['data'] = FaqResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/faqs/faqs-index', [
            'faqs' => $faqs,
            'search' => $filters,
        ]);
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->faqService->createFaq(new CreateFaqDTO(
            questionAr: $validated['question_ar'],
            questionEn: $validated['question_en'] ?? null,
            answerAr: $validated['answer_ar'],
            answerEn: $validated['answer_en'] ?? null,
            sortOrder: (int) $validated['sort_order'],
            isPublished: (bool) $validated['is_published'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ created successfully.')]);

        return back();
    }

    public function update(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validated();

        $this->faqService->updateFaq($faq, new UpdateFaqDTO(
            questionAr: $validated['question_ar'],
            questionEn: $validated['question_en'] ?? null,
            answerAr: $validated['answer_ar'],
            answerEn: $validated['answer_en'] ?? null,
            sortOrder: (int) $validated['sort_order'],
            isPublished: (bool) $validated['is_published'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ updated successfully.')]);

        return back();
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $this->faqService->deleteFaq($faq);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ deleted successfully.')]);

        return back();
    }

    public function bulkDestroy(BulkDestroyFaqRequest $request): RedirectResponse
    {
        $this->faqService->bulkDelete($request->validated('ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQs deleted successfully.')]);

        return back();
    }

    public function restore(RestoreFaqRequest $request, int|string $id): RedirectResponse
    {
        $this->faqService->restoreFaq($id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('FAQ restored successfully.')]);

        return back();
    }
}
