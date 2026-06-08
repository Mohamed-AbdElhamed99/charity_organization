<?php

namespace App\Contracts\Services;

use App\DTOs\CreateFaqDTO;
use App\DTOs\UpdateFaqDTO;
use App\Models\Faq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FaqServiceInterface
{
    public function getPaginatedFaqs(array $filters): LengthAwarePaginator;

    public function getPublishedFaqs(): Collection;

    public function createFaq(CreateFaqDTO $dto): Faq;

    public function updateFaq(Faq $faq, UpdateFaqDTO $dto): Faq;

    public function deleteFaq(Faq $faq): void;

    public function restoreFaq(int|string $id): Faq;

    public function bulkDelete(array $ids): void;
}
