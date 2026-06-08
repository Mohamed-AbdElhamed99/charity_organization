<?php

namespace App\Http\Controllers\Site;

use App\Contracts\Services\LegalDocumentServiceInterface;
use App\Enums\LegalDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Site\LegalDocumentResource;
use Inertia\Inertia;
use Inertia\Response;

class LegalDocumentController extends Controller
{
    public function __construct(
        private readonly LegalDocumentServiceInterface $legalDocumentService,
    ) {}

    public function terms(): Response
    {
        return $this->render(LegalDocumentType::Terms);
    }

    public function privacy(): Response
    {
        return $this->render(LegalDocumentType::Privacy);
    }

    private function render(LegalDocumentType $type): Response
    {
        $document = $this->legalDocumentService->getByType($type);

        return Inertia::render('site/legal/legal-document-show', [
            'document' => (new LegalDocumentResource($document))->resolve(),
        ]);
    }
}
