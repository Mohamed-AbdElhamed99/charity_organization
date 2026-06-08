<?php

namespace App\Services;

use App\Contracts\Services\LegalDocumentServiceInterface;
use App\DTOs\UpdateLegalDocumentDTO;
use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;

class LegalDocumentService implements LegalDocumentServiceInterface
{
    public function getByType(LegalDocumentType $type): LegalDocument
    {
        return LegalDocument::query()
            ->where('type', $type)
            ->firstOrFail();
    }

    public function updateDocument(LegalDocument $document, UpdateLegalDocumentDTO $dto): LegalDocument
    {
        $document->update([
            'title_ar' => $dto->titleAr,
            'title_en' => $dto->titleEn,
            'body_ar' => $dto->bodyAr,
            'body_en' => $dto->bodyEn,
        ]);

        return $document->fresh();
    }
}
