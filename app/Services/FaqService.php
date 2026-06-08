<?php

namespace App\Services;

use App\Contracts\Services\FaqServiceInterface;
use App\DTOs\CreateFaqDTO;
use App\DTOs\UpdateFaqDTO;
use App\Models\Faq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FaqService implements FaqServiceInterface
{
    public function getPaginatedFaqs(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $status = $filters['status'] ?? null;

        return Faq::query()
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('question_ar', 'like', "%{$query}%")
                        ->orWhere('question_en', 'like', "%{$query}%");
                });
            })
            ->when($status, function ($builder) use ($status) {
                $statuses = is_array($status) ? $status : [$status];

                $builder->where(function ($q) use ($statuses) {
                    foreach ($statuses as $statusValue) {
                        match ($statusValue) {
                            'published' => $q->orWhere('is_published', true),
                            'draft' => $q->orWhere('is_published', false),
                            default => null,
                        };
                    }
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function getPublishedFaqs(): Collection
    {
        return Faq::query()->published()->get();
    }

    public function createFaq(CreateFaqDTO $dto): Faq
    {
        return Faq::create([
            'question_ar' => $dto->questionAr,
            'question_en' => $dto->questionEn,
            'answer_ar' => $dto->answerAr,
            'answer_en' => $dto->answerEn,
            'sort_order' => $dto->sortOrder,
            'is_published' => $dto->isPublished,
        ]);
    }

    public function updateFaq(Faq $faq, UpdateFaqDTO $dto): Faq
    {
        $faq->update([
            'question_ar' => $dto->questionAr,
            'question_en' => $dto->questionEn,
            'answer_ar' => $dto->answerAr,
            'answer_en' => $dto->answerEn,
            'sort_order' => $dto->sortOrder,
            'is_published' => $dto->isPublished,
        ]);

        return $faq->fresh();
    }

    public function deleteFaq(Faq $faq): void
    {
        $faq->delete();
    }

    public function restoreFaq(int|string $id): Faq
    {
        $faq = Faq::withTrashed()->findOrFail($id);
        $faq->restore();

        return $faq;
    }

    public function bulkDelete(array $ids): void
    {
        Faq::query()
            ->whereIn('id', $ids)
            ->delete();
    }
}
