<?php

namespace App\Http\Controllers\Site;

use App\Contracts\Services\FaqServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Site\FaqResource;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function __construct(
        private readonly FaqServiceInterface $faqService,
    ) {}

    public function index(): Response
    {
        $faqs = FaqResource::collection(
            $this->faqService->getPublishedFaqs()
        )->resolve();

        return Inertia::render('site/faqs/faqs-index', [
            'faqs' => $faqs,
        ]);
    }
}
