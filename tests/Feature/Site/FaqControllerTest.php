<?php

namespace Tests\Feature\Site;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FaqControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_published_faqs_only(): void
    {
        Faq::factory()->published()->count(2)->create(['sort_order' => 1]);
        Faq::factory()->draft()->count(3)->create();

        $this->get(route('faqs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/faqs/faqs-index')
                ->has('faqs', 2)
            );
    }
}
