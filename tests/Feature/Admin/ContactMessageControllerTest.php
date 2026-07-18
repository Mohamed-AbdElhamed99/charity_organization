<?php

namespace Tests\Feature\Admin;

use App\Models\ContactUs;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_guests_cannot_access_contact_messages_index(): void
    {
        $this->get(route('admin.contact-messages.index'))
            ->assertNotFound();
    }

    public function test_authorized_user_can_view_contact_messages_index(): void
    {
        $user = $this->createAuthorizedUser();
        ContactUs::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('admin.contact-messages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/contact-messages/contact-messages-index')
                ->has('messages.data', 3)
            );
    }

    public function test_authorized_user_can_view_contact_message(): void
    {
        $user = $this->createAuthorizedUser();
        $message = ContactUs::factory()->unreviewed()->create();

        $this->actingAs($user)
            ->get(route('admin.contact-messages.show', $message))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/contact-messages/contact-messages-show')
                ->where('message.id', $message->id)
            );
    }

    public function test_authorized_user_can_mark_message_as_reviewed(): void
    {
        $user = $this->createAuthorizedUser();
        $message = ContactUs::factory()->unreviewed()->create();

        $this->actingAs($user)
            ->patch(route('admin.contact-messages.mark-reviewed', $message), [
                'review_notes' => 'Handled by phone.',
            ])
            ->assertRedirect();

        $message->refresh();

        $this->assertTrue($message->is_reviewed);
        $this->assertSame($user->id, $message->reviewed_by);
        $this->assertSame('Handled by phone.', $message->review_notes);
    }

    public function test_authorized_user_can_delete_contact_message(): void
    {
        $user = $this->createAuthorizedUser();
        $message = ContactUs::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.contact-messages.destroy', $message))
            ->assertRedirect();

        $this->assertSoftDeleted('contact_us', ['id' => $message->id]);
    }
}
