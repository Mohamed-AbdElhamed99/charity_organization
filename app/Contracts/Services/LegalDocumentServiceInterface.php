<?php

namespace App\Contracts\Services;

use App\DTOs\UpdateLegalDocumentDTO;
use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;

interface LegalDocumentServiceInterface
{
    public function getByType(LegalDocumentType $type): LegalDocument;

    public function updateDocument(LegalDocument $document, UpdateLegalDocumentDTO $dto): LegalDocument;
}
