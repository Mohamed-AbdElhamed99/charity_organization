<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\LegalDocumentServiceInterface;
use App\DTOs\UpdateLegalDocumentDTO;
use App\Enums\LegalDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LegalDocument\UpdateLegalDocumentRequest;
use App\Http\Resources\Admin\LegalDocument\LegalDocumentResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LegalDocumentController extends Controller
{
    public function __construct(
        private readonly LegalDocumentServiceInterface $legalDocumentService,
    ) {}

    public function editTerms(): Response
    {
        return $this->edit(LegalDocumentType::Terms);
    }

    public function editPrivacy(): Response
    {
        return $this->edit(LegalDocumentType::Privacy);
    }

    public function updateTerms(UpdateLegalDocumentRequest $request): RedirectResponse
    {
        return $this->update($request, LegalDocumentType::Terms);
    }

    public function updatePrivacy(UpdateLegalDocumentRequest $request): RedirectResponse
    {
        return $this->update($request, LegalDocumentType::Privacy);
    }

    private function edit(LegalDocumentType $type): Response
    {
        $document = $this->legalDocumentService->getByType($type);

        return Inertia::render('admin/legal/legal-document-edit', [
            'document' => (new LegalDocumentResource($document))->resolve(),
            'documentType' => $type->value,
            'documentLabel' => $type->label(),
        ]);
    }

    private function update(UpdateLegalDocumentRequest $request, LegalDocumentType $type): RedirectResponse
    {
        $validated = $request->validated();
        $document = $this->legalDocumentService->getByType($type);

        $this->legalDocumentService->updateDocument($document, new UpdateLegalDocumentDTO(
            titleAr: $validated['title_ar'],
            titleEn: $validated['title_en'] ?? null,
            bodyAr: $validated['body_ar'],
            bodyEn: $validated['body_en'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document updated successfully.')]);

        return back();
    }
}
