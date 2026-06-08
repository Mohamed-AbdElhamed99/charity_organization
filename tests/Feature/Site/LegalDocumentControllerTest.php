<?php

namespace Tests\Feature\Site;

use Database\Seeders\LegalDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LegalDocumentSeeder::class);
    }

    public function test_public_can_view_terms_page(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/legal/legal-document-show')
                ->has('document.title')
                ->has('document.body')
            );
    }

    public function test_public_can_view_privacy_page(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/legal/legal-document-show')
                ->where('document.type', 'privacy')
            );
    }
}
