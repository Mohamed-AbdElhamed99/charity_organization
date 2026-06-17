<?php

namespace App\Services;

use App\Contracts\Services\LegalDocumentServiceInterface;
use App\DTOs\UpdateLegalDocumentDTO;
use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;

class LegalDocumentService implements LegalDocumentServiceInterface
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

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
            // body_ar is NOT NULL in the DB; coalesce to empty string to satisfy
            // the column constraint if sanitization strips the entire content.
            'body_ar' => $this->sanitizer->sanitize($dto->bodyAr) ?? '',
            'body_en' => $this->sanitizer->sanitize($dto->bodyEn),
        ]);

        return $document->fresh();
    }
}
