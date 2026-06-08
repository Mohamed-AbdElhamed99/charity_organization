<?php

namespace Tests\Feature\Site;

use App\Mail\ContactMessageConfirmationMailable;
use App\Mail\ContactMessageReceivedMailable;
use App\Models\ContactUs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'fullname' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'subject' => 'General Inquiry',
            'message' => 'Hello, I would like more information.',
            '_hp' => '',
        ], $overrides);
    }

    public function test_public_can_view_contact_page(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/contact/contact-index')
            );
    }

    public function test_public_can_submit_contact_form(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('contact_us', [
            'email' => 'john@example.com',
            'subject' => 'General Inquiry',
        ]);

        Mail::assertQueued(ContactMessageReceivedMailable::class);
        Mail::assertQueued(ContactMessageConfirmationMailable::class);
    }

    public function test_honeypot_rejects_spam_submissions(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->validPayload([
            '_hp' => 'spam-bot',
        ]))->assertSessionHasErrors('_hp');

        $this->assertDatabaseCount('contact_us', 0);
        Mail::assertNothingQueued();
    }

    public function test_contact_form_persists_even_when_mail_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Mail failed'));

        $this->post(route('contact.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertSame(1, ContactUs::count());
    }
}
